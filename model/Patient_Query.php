<?php
/**
 * This file is part of OpenClinic
 *
 * Copyright (c) 2002-2004 jact
 * Licensed under the GNU GPL. For full terms see the file LICENSE.
 *
 * $Id: Patient_Query.php,v 1.1 2004/02/28 19:52:00 jact Exp $
 */

/**
 * Patient_Query.php
 ********************************************************************
 * Contains the class Patient_Query
 ********************************************************************
 * Author: jact <jachavar@terra.es>
 * Last modified: 28/02/04 20:52
 */

require_once("../classes/Query.php");
require_once("../classes/Patient.php");

/**
 * Patient_Query data access component for patients
 ********************************************************************
 * @author jact <jachavar@terra.es>
 * @version 0.3
 * @access public
 ********************************************************************
 * Methods:
 *  void setItemsPerPage(int $value)
 *  int getCurrentRow(void)
 *  int getRowCount(void)
 *  int getPageCount(void)
 *  bool search(int $type, array $word, int $page, string $logical, int $limitFrom = 0)
 *  mixed getLastId(void)
 *  mixed select(int $idPatient)
 *  mixed fetchPatient(void)
 *  bool existName(string $firstName, string $surname1, string $surname2, int $idPatient = 0)
 *  bool insert(Patient $patient)
 *  bool update(Patient $patient)
 *  bool delete(int $idPatient)
 */
class Patient_Query extends Query
{
  var $_itemsPerPage = 10;
  var $_rowNumber = 0;
  var $_currentRow = 0;
  var $_currentPage = 0;
  var $_rowCount = 0;
  var $_pageCount = 0;

  /**
   * void setItemsPerPage(int $value)
   ********************************************************************
   * @param int $value
   * @access public
   */
  function setItemsPerPage($value)
  {
    $this->_itemsPerPage = intval($value);
  }

  /**
   * int getCurrentRow(void)
   ********************************************************************
   * @return int
   * @access public
   */
  function getCurrentRow()
  {
    return intval($this->_currentRow);
  }

  /**
   * int getRowCount(void)
   ********************************************************************
   * @return int
   * @access public
   */
  function getRowCount()
  {
    return intval($this->_rowCount);
  }

  /**
   * int getPageCount(void)
   ********************************************************************
   * @return int
   * @access public
   */
  function getPageCount()
  {
    return intval($this->_pageCount);
  }

  /**
   * bool search(int $type, array $word, int $page, string $logical, int $limitFrom = 0)
   ********************************************************************
   * Executes a query search
   ********************************************************************
   * @param int $type one of the global constants
   * @param array (string) $word string(s) to search for
   * @param int $page What page should be returned if results are more than one page
   * @param string $logical logical operator to concatenate string(s) to search for
   * @param int $limitFrom (optional) maximum number of results
   * @return boolean returns false, if error occurs
   * @access public
   */
  function search($type, $word, $page, $logical, $limitFrom = 0)
  {
    // reset stats
    $this->_rowNumber = 0;
    $this->_currentRow = 0;
    $this->_currentPage = intval($page);
    $this->_rowCount = 0;
    $this->_pageCount = 0;

    // Building sql statements
    switch ($type)
    {
      case OPEN_SEARCH_SURNAME1:
        $field = "surname1";
        break;

      case OPEN_SEARCH_SURNAME2:
        $field = "surname2";
        break;

      case OPEN_SEARCH_FIRSTNAME:
        $field = "first_name";
        break;

      case OPEN_SEARCH_NIF:
        $field = "nif";
        break;

      case OPEN_SEARCH_NTS:
        $field = "nts";
        break;

      case OPEN_SEARCH_NSS:
        $field = "nss";
        break;

      case OPEN_SEARCH_BIRTHPLACE:
        $field = "birth_place";
        break;

      case OPEN_SEARCH_ADDRESS:
        $field = "address";
        break;

      case OPEN_SEARCH_PHONE:
        $field = "phone_contact";
        break;

      case OPEN_SEARCH_INSURANCE:
        $field = "insurance_company";
        break;

      case OPEN_SEARCH_COLLEGIATE:
        $field = "collegiate_number";
        break;

      default:
        $field = "no_field";
        break;
    }

    // Building sql statements
    $sql = "FROM patient_tbl WHERE ";
    $num = sizeof($word);
    if ($num > 1)
    {
      for ($i = 0; $i < ($num - 1); $i++)
      {
        if ($logical == OPEN_NOT)
        {
          $sql .= $field . " NOT LIKE '%" . $word[$i] . "%' AND ";
        }
        else
        {
          $sql .= $field . " LIKE '%" . $word[$i] . "%' " . $logical . " ";
        }
      }
    }
    if ($logical == OPEN_NOT)
    {
      $sql .= $field . " NOT LIKE '%" . $word[$num - 1] . "%'";
    }
    else
    {
      $sql .= $field . " LIKE '%" . $word[$num - 1] . "%'";
    }

    $sqlCount = "SELECT COUNT(*) AS row_count " . $sql;

    $sql = "SELECT *,FLOOR((TO_DAYS(GREATEST(IF(decease_date='0000-00-00',CURRENT_DATE,decease_date),IFNULL(decease_date,CURRENT_DATE))) - TO_DAYS(birth_date)) / 365) AS age " . $sql;
    $sql .= " ORDER BY surname1, surname2, first_name";
    // setting limit so we can page through the results
    $offset = ($this->_currentPage - 1) * intval($this->_itemsPerPage);
    if ($offset >= $limitFrom && $limitFrom > 0)
    {
      $offset = 0;
    }
    $limitTo = intval($this->_itemsPerPage);
    $sql .= " LIMIT " . $offset . "," . $limitTo . ";";

    //echo "limitFrom=[" . $limitFrom . "]<br />\n"; // debug
    //echo "offset=[" . $offset . "]<br />\n"; // debug
    //echo "sql=[" . $sql . "]<br />\n"; // debug

    // Running row count sql statement
    $countResult = $this->exec($sqlCount);
    if ($countResult == false)
    {
      $this->_error = "Error counting patient search results.";
      return false;
    }

    // Calculate stats based on row count
    $array = $this->fetchRow();
    $this->_rowCount = $array["row_count"];
    if ($limitFrom > 0 && $limitFrom < $this->_rowCount)
    {
      $this->_rowCount = $limitFrom;
    }
    $this->_pageCount = ceil($this->_rowCount / intval($this->_itemsPerPage));

    // Running search sql statement
    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error searching patient information.";
      return false;
    }

    return $result;
  }

  /**
   * mixed getLastId(void)
   ********************************************************************
   * Executes a query
   ********************************************************************
   * @return mixed if error occurs returns false, else last insert id
   * @access public
   */
  function getLastId()
  {
    $sql = "SELECT LAST_INSERT_ID() AS last_id";
    $sql .= " FROM patient_tbl";

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error accessing last id information.";
      return false;
    }

    $array = $this->fetchRow();
    return ($array == false ? 0 : $array["last_id"]);
  }

  /**
   * mixed select(int $idPatient)
   ********************************************************************
   * Executes a query
   ********************************************************************
   * @param int $idPatient key of patient to select
   * @return mixed if error occurs returns false, else number of rows in the result
   * @access public
   */
  function select($idPatient)
  {
    $sql = "SELECT *,";
    $sql .= "FLOOR((TO_DAYS(GREATEST(IF(decease_date='0000-00-00',CURRENT_DATE,decease_date),IFNULL(decease_date,CURRENT_DATE))) - TO_DAYS(birth_date)) / 365) AS age";
    $sql .= " FROM patient_tbl";
    $sql .= " WHERE id_patient=" . intval($idPatient);

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error accessing patient information.";
      return false;
    }

    return $this->numRows();
  }

  /**
   * mixed fetchPatient(void)
   ********************************************************************
   * Fetches a row from the query result and populates the Patient object.
   ********************************************************************
   * @return mixed returns patient or false if no more patients to fetch
   * @access public
   */
  function fetchPatient()
  {
    $array = $this->fetchRow();
    if ($array == false)
    {
      return false;
    }

    // increment rowNumber
    $this->_rowNumber = $this->_rowNumber + 1;
    $this->_currentRow = $this->_rowNumber + (($this->_currentPage - 1) * $this->_itemsPerPage);

    $patient = new Patient();
    $patient->setIdPatient($array["id_patient"]);
    //$patient->setLastUpdateDate($array["last_update_date"]);
    $patient->setCollegiateNumber(urldecode($array["collegiate_number"]));
    $patient->setNIF(urldecode($array["nif"]));
    $patient->setFirstName(urldecode($array["first_name"]));
    $patient->setSurname1(urldecode($array["surname1"]));
    $patient->setSurname2(urldecode($array["surname2"]));
    $patient->setAddress(urldecode($array["address"]));
    $patient->setPhone(urldecode($array["phone_contact"]));
    $patient->setSex($array["sex"]);
    $patient->setRace(urldecode($array["race"]));
    $patient->setBirthDate($array["birth_date"]);
    $patient->setAge($array["age"]);
    $patient->setBirthPlace(urldecode($array["birth_place"]));
    $patient->setDeceaseDate($array["decease_date"]);
    $patient->setNTS(urldecode($array["nts"]));
    $patient->setNSS(urldecode($array["nss"]));
    $patient->setFamilySituation(urldecode($array["family_situation"]));
    $patient->setLabourSituation(urldecode($array["labour_situation"]));
    $patient->setEducation(urldecode($array["education"]));
    $patient->setInsuranceCompany(urldecode($array["insurance_company"]));

    return $patient;
  }

  /**
   * bool existName(string $firstName, string $surname1, string $surname2, int $idPatient = 0)
   ********************************************************************
   * Returns true if patient name already exists
   ********************************************************************
   * @param string $firstName
   * @param string $surname1
   * @param string $surname2
   * @param int $idPatient (optional) key of patient
   * @return boolean returns true if name already exists
   * @access public
   */
  function existName($firstName, $surname1, $surname2, $idPatient = 0)
  {
    $sql = "SELECT COUNT(id_patient)";
    $sql .= " FROM patient_tbl";
    $sql .= " WHERE first_name='" . urlencode($firstName) . "'";
    $sql .= " AND surname1='" . urlencode($surname1) . "'";
    $sql .= " AND surname2='" . urlencode($surname2) . "'";
    if ($idPatient > 0)
    {
      $sql .= " AND id_patient<>" . intval($idPatient);
    }

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error checking for dup name.";
      return false;
    }

    $array = $this->fetchRow(MYSQL_NUM);

    return ($array[0] > 0);
  }

  /**
   * bool insert(Patient $patient)
   ********************************************************************
   * Inserts a new patient into the database.
   ********************************************************************
   * @param Patient $patient patient to insert
   * @return boolean returns false, if error occurs
   * @access public
   */
  function insert($patient)
  {
    /*$isDupName = $this->existName($patient->getFirstName(), $patient->getSurname1(), $patient->getSurname2());
    if ($this->errorOccurred())
    {
      return false;
    }

    if ($isDupName)
    {
      $this->_error = "Patient name, " . $patient->getFirstName();
      $this->_error .= " " . $patient->getSurname1();
      $this->_error .= " " . $patient->getSurname2() . ", is already in use.";
      return false;
    }*/

    $sql = "INSERT INTO patient_tbl ";
    $sql .= "(id_patient, nif, first_name, surname1, surname2, address, ";
    $sql .= "phone_contact, sex, race, birth_date, birth_place, decease_date, nts, nss, ";
    $sql .= "family_situation, labour_situation, education, insurance_company, ";
    $sql .= "collegiate_number) VALUES (NULL, ";
    //$sql .= "'" . $patient->getLastUpdateDate(false) . "', ";
    $sql .= ($patient->getNIF() == "") ? "NULL, " : "'" . urlencode($patient->getNIF()) . "', ";
    $sql .= "'" . urlencode($patient->getFirstName()) . "', ";
    $sql .= "'" . urlencode($patient->getSurname1()) . "', ";
    $sql .= "'" . urlencode($patient->getSurname2()) . "', ";
    $sql .= ($patient->getAddress() =="") ? "NULL, " : "'" . urlencode($patient->getAddress()) . "', ";
    $sql .= ($patient->getPhone() == "") ? "NULL, " : "'" . urlencode($patient->getPhone()) . "', ";
    $sql .= "'" . $patient->getSex() . "', ";
    $sql .= ($patient->getRace() == "") ? "NULL, " : "'" . urlencode($patient->getRace()) . "', ";
    $sql .= ($patient->getBirthDate(false) == "") ? "NULL, " : "'" . $patient->getBirthDate(false) . "', ";
    $sql .= ($patient->getBirthPlace() == "") ? "NULL, " : "'" . urlencode($patient->getBirthPlace()) . "', ";
    $sql .= ($patient->getDeceaseDate(false) == "") ? "NULL, " : "'" . $patient->getDeceaseDate(false) . "', ";
    $sql .= ($patient->getNTS() == "") ? "NULL, " : "'" . urlencode($patient->getNTS()) . "', ";
    $sql .= ($patient->getNSS() == "") ? "NULL, " : "'" . urlencode($patient->getNSS()) . "', ";
    $sql .= ($patient->getFamilySituation() == "") ? "NULL, " : "'" . urlencode($patient->getFamilySituation()) . "', ";
    $sql .= ($patient->getLabourSituation() == "") ? "NULL, " : "'" . urlencode($patient->getLabourSituation()) . "', ";
    $sql .= ($patient->getEducation() == "") ? "NULL, " : "'" . urlencode($patient->getEducation()) . "', ";
    $sql .= ($patient->getInsuranceCompany() == "") ? "NULL, " : "'" . urlencode($patient->getInsuranceCompany()) . "', ";
    $sql .= ($patient->getCollegiateNumber() == "") ? "NULL);" : "'" . urlencode($patient->getCollegiateNumber()) . "');";

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error inserting new patient information.";
      return false;
    }

    $sql = "INSERT INTO history_tbl (id_patient) VALUES (";
    $sql .= $this->getLastId() . ");";

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error inserting new patient information in history.";
    }

    return $result;
  }

  /**
   * bool update(Patient $patient)
   ********************************************************************
   * Update a patient in the database.
   ********************************************************************
   * @param Patient $patient patient to update
   * @return boolean returns false, if error occurs
   * @access public
   */
  function update($patient)
  {
    /*$isDupName = $this->existName($patient->getFirstName(), $patient->getSurname1(), $patient->getSurname2(), $patient->getIdPatient());
    if ($this->errorOccurred())
    {
      return false;
    }

    if ($isDupName)
    {
      $this->_errorOccurred = true;
      $this->_error = "Patient name, " . $patient->getFirstName();
      $this->_error .= " " . $patient->getSurname1();
      $this->_error .= " " . $patient->getSurname2() . ", is already in use.";
      return false;
    }*/

    $sql = "UPDATE patient_tbl SET ";
    //$sql .= "last_update_date=curdate(), ";
    $sql .= "nif=" . (($patient->getNIF() == "") ? "NULL, " : "'" . urlencode($patient->getNIF()) . "', ");
    $sql .= "first_name='" . urlencode($patient->getFirstName()) . "', ";
    $sql .= "surname1='" . urlencode($patient->getSurname1()) . "', ";
    $sql .= "surname2='" . urlencode($patient->getSurname2()) . "', ";
    $sql .= "address=" . (($patient->getAddress() =="") ? "NULL, " : "'" . urlencode($patient->getAddress()) . "', ");
    $sql .= "phone_contact=" . (($patient->getPhone() == "") ? "NULL, " : "'" . urlencode($patient->getPhone()) . "', ");
    $sql .= "sex='" . $patient->getSex() . "', ";
    $sql .= "race=" . (($patient->getRace() == "") ? "NULL, " : "'" . urlencode($patient->getRace()) . "', ");
    $sql .= "birth_date=" . (($patient->getBirthDate(false) == "") ? "NULL, " : "'" . $patient->getBirthDate(false) . "', ");
    $sql .= "birth_place=" . (($patient->getBirthPlace() == "") ? "NULL, " : "'" . urlencode($patient->getBirthPlace()) . "', ");
    $sql .= "decease_date=" . (($patient->getDeceaseDate(false) == "") ? "NULL, " : "'" . $patient->getDeceaseDate(false) . "', ");
    $sql .= "nts=" . (($patient->getNTS() == "") ? "NULL, " : "'" . urlencode($patient->getNTS()) . "', ");
    $sql .= "nss=" . (($patient->getNSS() == "") ? "NULL, " : "'" . urlencode($patient->getNSS()) . "', ");
    $sql .= "family_situation=" . (($patient->getFamilySituation() == "") ? "NULL, " : "'" . urlencode($patient->getFamilySituation()) . "', ");
    $sql .= "labour_situation=" . (($patient->getLabourSituation() == "") ? "NULL, " : "'" . urlencode($patient->getLabourSituation()) . "', ");
    $sql .= "education=" . (($patient->getEducation() == "") ? "NULL, " : "'" . urlencode($patient->getEducation()) . "', ");
    $sql .= "insurance_company=" . (($patient->getInsuranceCompany() == "") ? "NULL, " : "'" . urlencode($patient->getInsuranceCompany()) . "', ");
    $sql .= "collegiate_number=" . (($patient->getCollegiateNumber() == "") ? "NULL " : "'" . urlencode($patient->getCollegiateNumber()) . "' ");
    $sql .= "WHERE id_patient=" . $patient->getIdPatient();

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error updating patient information.";
    }

    return $result;
  }

  /**
   * bool delete(int $idPatient)
   ********************************************************************
   * Deletes a patient from the database.
   ********************************************************************
   * @param int $idPatient key of patient to delete
   * @return boolean returns false, if error occurs
   * @access public
   */
  function delete($idPatient)
  {
    $sql = "DELETE FROM patient_tbl";
    $sql .= " WHERE id_patient=" . intval($idPatient);

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error deleting patient information.";
      return false;
    }

    $sql = "DELETE FROM history_tbl";
    $sql .= " WHERE id_patient=" . intval($idPatient);

    $result = $this->exec($sql);
    if ($result == false)
    {
      $this->_error = "Error deleting patient information.";
    }

    return $result;
  }
} // end class
?>