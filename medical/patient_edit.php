<?php
/**
 * This file is part of OpenClinic
 *
 * Copyright (c) 2002-2004 jact
 * Licensed under the GNU GPL. For full terms see the file LICENSE.
 *
 * $Id: patient_edit.php,v 1.2 2004/04/24 14:52:14 jact Exp $
 */

/**
 * patient_edit.php
 ********************************************************************
 * Patient edition process
 ********************************************************************
 * Author: jact <jachavar@terra.es>
 */

  ////////////////////////////////////////////////////////////////////
  // Checking for post vars. Go back to form if none found.
  ////////////////////////////////////////////////////////////////////
  if (count($_POST) == 0)
  {
    header("Location: ../medical/patient_search_form.php");
    exit();
  }

  ////////////////////////////////////////////////////////////////////
  // Controlling vars
  ////////////////////////////////////////////////////////////////////
  $tab = "medical";
  $nav = "social";
  $onlyDoctor = false;
  $errorLocation = "../medical/patient_edit_form.php";

  require_once("../shared/read_settings.php");
  require_once("../shared/login_check.php");
  require_once("../classes/Patient_Query.php");
  require_once("../lib/error_lib.php");
  require_once("../shared/record_log.php"); // record log

  ////////////////////////////////////////////////////////////////////
  // Validate data
  ////////////////////////////////////////////////////////////////////
  $idPatient = intval($_POST["id_patient"]);
  $patName = urldecode($_POST["first_name"] . " " . $_POST["surname1"] . " " . $_POST["surname2"]);

  $pat = new Patient();

  $pat->setIdPatient($_POST["id_patient"]);

  require_once("../medical/patient_validate_post.php");

  $returnLocation = "../medical/patient_view.php?key=" . $idPatient . "&amp;reset=Y";

  ////////////////////////////////////////////////////////////////////
  // Prevent user from aborting script
  ////////////////////////////////////////////////////////////////////
  $oldAbort = ignore_user_abort(true);

  ////////////////////////////////////////////////////////////////////
  // Update patient
  ////////////////////////////////////////////////////////////////////
  $patQ = new Patient_Query();
  $patQ->connect();
  if ($patQ->errorOccurred())
  {
    showQueryError($patQ);
  }

  if ($patQ->existName($pat->getFirstName(), $pat->getSurname1(), $pat->getSurname2(), $pat->getIdPatient()))
  {
    $patQ->close();
    include_once("../shared/header.php");

    echo '<p>' . sprintf(_("Patient name, %s, is already in use. The changes have no effect."), $patName) . "</p>\n";

    echo '<p><a href="' . $returnLocation . '">' . _("Return to Patient Social Data") . "</a></p>\n";

    include_once("../shared/footer.php");
    exit();
  }

  if ( !$patQ->update($pat) )
  {
    $patQ->close();
    showQueryError($patQ);
  }
  $patQ->close();
  unset($patQ);
  unset($pat);

  ////////////////////////////////////////////////////////////////////
  // Record log process
  ////////////////////////////////////////////////////////////////////
  recordLog("patient_tbl", "UPDATE", $idPatient);

  ////////////////////////////////////////////////////////////////////
  // Reset abort setting
  ////////////////////////////////////////////////////////////////////
  ignore_user_abort($oldAbort);

  ////////////////////////////////////////////////////////////////////
  // Destroy form values and errors
  ////////////////////////////////////////////////////////////////////
  unset($_SESSION["postVars"]);
  unset($_SESSION["pageErrors"]);

  ////////////////////////////////////////////////////////////////////
  // Show success page
  ////////////////////////////////////////////////////////////////////
  $title = _("Edit Patient Social Data");
  require_once("../shared/header.php");

  ////////////////////////////////////////////////////////////////////
  // Navigation links
  ////////////////////////////////////////////////////////////////////
  require_once("../shared/navigation_links.php");
  $links = array(
    _("Medical Records") => "../medical/index.php",
    _("Search Patient") => "../medical/patient_search_form.php",
    _("Social Data") => $returnLocation,
    $title => ""
  );
  showNavLinks($links, "patient.png");
  unset($links);

  echo '<p>' . sprintf(_("Patient, %s, has been updated."), $patName) . "</p>\n";

  echo '<p><a href="' . $returnLocation . '">' . _("Return to Patient Social Data") . "</a></p>\n";

  require_once("../shared/footer.php");
?>