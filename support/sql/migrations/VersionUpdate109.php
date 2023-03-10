<?php
/** ---------------------------------------------------------------------
 * app/lib/VersionUpdate109.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Installer
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 require_once(__CA_LIB_DIR__.'/BaseVersionUpdater.php');
 require_once(__CA_LIB_DIR__."/Db.php");
 require_once(__CA_MODELS_DIR__."/ca_relationship_types.php");
 require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 
	class VersionUpdate109 extends BaseVersionUpdater {
		# -------------------------------------------------------
		protected $opn_schema_update_to_version_number = 109;
		
		# -------------------------------------------------------
		/**
		 *
		 * @return string HTML to display after update
		 */
		public function getPostupdateMessage() {
			return _t("The CollectiveAccess authentication back-end had a major overhaul in this update. If you previously used the stock authentication configuration, there is no further action necessary. However, if you have made changes to your authentication.conf configuration file, chances are that you won't be able to log into your system. Please take a close look at the new stock file and change your local configuration accordingly.");
		}
		# -------------------------------------------------------
	}
?>
