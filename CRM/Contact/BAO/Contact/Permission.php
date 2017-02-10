<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Contact_BAO_Contact_Permission {

  /**
   * Check if the logged in user has permissions for the operation type.
   *
   * @param int $id
   *   Contact id.
   * @param int|string $type the type of operation (view|edit)
   *
   * @return bool
   *   true if the user has permission, false otherwise
   */
  public static function allow($id, $type = CRM_Core_Permission::VIEW) {
    $tables = array();
    $whereTables = array();


    // short circuit for admin rights here so we avoid unneeeded queries
    // some duplication of code, but we skip 3-5 queries
    if (CRM_Core_Permission::check('edit all contacts') ||
      ($type == CRM_ACL_API::VIEW && CRM_Core_Permission::check('view all contacts'))
    ) {
      return TRUE;
    }

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables);
    $from = CRM_Contact_BAO_Query::fromClause($whereTables);

    $query = "SELECT contact_a.id $from WHERE contact_a.id = %1 AND $permission LIMIT 1";
    if (CRM_Core_DAO::singleValueQuery($query, array(1 => array($id, 'Integer')))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if there are any contacts in cache table.
   *
   * @param int|string $type the type of operation (view|edit)
   * @param int $contactID
   *   Contact id.
   *
   * @return bool
   */
  /*public static function hasContactsInCache(
    $type = CRM_Core_Permission::VIEW,
    $contactID = NULL
  ) {
    if (!$contactID) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if ($type = CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }

    // fill cache
    self::cache($contactID);

    $sql = "
SELECT id
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause LIMIT 1";

    $params = array(1 => array($contactID, 'Integer'));
    return (bool) CRM_Core_DAO::singleValueQuery($sql, $params);
  }*/


  /**
   * @param int $contactID
   * @param CRM_Core_Form $form
   * @param bool $redirect
   *
   * @return bool
   */
  public static function validateOnlyChecksum($contactID, &$form, $redirect = TRUE) {
    // check if this is of the format cs=XXX
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($contactID,
      CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)
    )
    ) {
      if ($redirect) {
        // also set a message in the UF framework
        $message = ts('You do not have permission to edit this contact record. Contact the site administrator if you need assistance.');
        CRM_Utils_System::setUFMessage($message);

        $config = CRM_Core_Config::singleton();
        CRM_Core_Error::statusBounce($message,
          $config->userFrameworkBaseURL
        );
        // does not come here, we redirect in the above statement
      }
      return FALSE;
    }

    // set appropriate AUTH source
    self::initChecksumAuthSrc(TRUE, $form);

    // so here the contact is posing as $contactID, lets set the logging contact ID variable
    // CRM-8965
    CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
      array(1 => array($contactID, 'Integer'))
    );

    return TRUE;
  }

  /**
   * @param bool $checkSumValidationResult
   * @param null $form
   */
  public static function initChecksumAuthSrc($checkSumValidationResult = FALSE, $form = NULL) {
    $session = CRM_Core_Session::singleton();
    if ($checkSumValidationResult && $form && CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)) {
      // if result is already validated, and url has cs, set the flag.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_CHECKSUM);
    }
    elseif (($session->get('authSrc') & CRM_Core_Permission::AUTH_SRC_CHECKSUM) == CRM_Core_Permission::AUTH_SRC_CHECKSUM) {
      // if checksum wasn't present in REQUEST OR checksum result validated as FALSE,
      // and flag was already set exactly as AUTH_SRC_CHECKSUM, unset it.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_UNKNOWN);
    }
  }

  /**
   * @param int $contactID
   * @param CRM_Core_Form $form
   * @param bool $redirect
   *
   * @return bool
   */
  public static function validateChecksumContact($contactID, &$form, $redirect = TRUE) {
    if (!self::allow($contactID, CRM_Core_Permission::EDIT)) {
      // check if this is of the format cs=XXX
      return self::validateOnlyChecksum($contactID, $form, $redirect);
    }
    return TRUE;
  }

}
