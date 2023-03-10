<?php
/** ---------------------------------------------------------------------
 * app/models/ca_entity_labels.php : table access class for table ca_entity_labels
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2021 Whirl-i-Gig
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_LIB_DIR__.'/BaseLabel.php');
require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
require_once(__CA_MODELS_DIR__.'/ca_entities.php');


BaseModel::$s_ca_models_definitions['ca_entity_labels'] = array(
 	'NAME_SINGULAR' 	=> _t('entity name'),
 	'NAME_PLURAL' 		=> _t('entity names'),
 	'FIELDS' 			=> array(
 		'label_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Label id', 'DESCRIPTION' => 'Identifier for Label'
		),
		'entity_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Entity id', 'DESCRIPTION' => 'Identifier for Entity'
		),
		'locale_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'DISPLAY_FIELD' => array('ca_locales.name'),
				'LABEL' => _t('Locale'), 'DESCRIPTION' => _t('Locale of label'),
		),
		'type_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				
				'LIST_CODE' => 'entity_label_types',
				'LABEL' => _t('Type'), 'DESCRIPTION' => _t('Indicates type of label and how it should be employed.')
		),
		'displayname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Display name'), 'DESCRIPTION' => _t('Name as it should be formatted for display (eg. in catalogues and exhibition label text). If you leave this blank the display name will be automatically derived from the input of other, more specific, fields.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'forename' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Forename'), 'DESCRIPTION' => _t('A given name that specifies and differentiates between members of a group of individuals, especially in a family, all of whose members usually share the same family name (surname). It is typically a name given to a person, as opposed to an inherited one such as a family name. You should place the primary forename - in cases where there is more than one this is usually the first listed - here.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'other_forenames' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Other forenames'), 'DESCRIPTION' => _t('Enter forenames other than the primary forename here.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'middlename' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 15, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Middle Name'), 'DESCRIPTION' => _t('Many names include one or more middle names, placed between the forename and the surname. In the Western world, a middle name is effectively a second given name. You should enter all middle names here. If there is more than one separate the names with spaces.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'surname' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 20, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Surname'), 'DESCRIPTION' => _t('A surname is a name added to a given name and is part of a personal name. In many cases a surname is a family name. For organizations this should be set to the full name.'),
				'BOUNDS_LENGTH' => array(0,512)
		),
		'prefix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Prefix'), 'DESCRIPTION' => _t('A prefix may be added to a name to signify veneration, a social position, an official position or a professional or academic qualification.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'suffix' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Suffix'), 'DESCRIPTION' => _t('A suffix may be added to a name to signify veneration, a social position, an official position or a professional or academic qualification.'),
				'BOUNDS_LENGTH' => array(0,100)
		),
		'name_sort' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 512, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Sort order', 'DESCRIPTION' => 'Sortable version of name value',
				'BOUNDS_LENGTH' => array(0,512)
		),
		'source_info' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => 'Source information', 'DESCRIPTION' => 'Source information'
		),
		'is_preferred' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_SELECT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Is preferred'), 'DESCRIPTION' => _t('Is preferred')
		)
 	)
);

class ca_entity_labels extends BaseLabel {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_entity_labels';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'label_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('displayname');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';

	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('displayname');

	# Maximum number of record to display per page in a listing
	protected $MAX_RECORDS_PER_PAGE = 20; 

	# How do you want to page through records in a listing: by number pages ordered
	# according to your setting above? Or alphabetically by the letters of the first
	# LIST_FIELD?
	protected $PAGE_SCHEME = 'alpha'; # alpha [alphabetical] or num [numbered pages; default]

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
			'entity_id'
		),
		"RELATED_TABLES" => array(
		
		)
	);
	
	
	# ------------------------------------------------------
	# Labels
	# ------------------------------------------------------
	# --- List of fields used in label user interface
	protected $LABEL_UI_FIELDS = array(
		'forename', 'other_forenames', 'middlename', 'surname', 'prefix', 'suffix', 'displayname'
	);
	protected $LABEL_DISPLAY_FIELD = 'displayname';
	
	# --- List of label fields that may be used to generate the display field
	protected $LABEL_SECONDARY_DISPLAY_FIELDS = ['forename', 'surname'];
	
	# --- Name of field used for sorting purposes
	protected $LABEL_SORT_FIELD = 'name_sort';
	
	# --- Name of table this table contains label for
	protected $LABEL_SUBJECT_TABLE = 'ca_entities';
	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	# ------------------------------------------------------
	# --- Constructor
	#
	# This is a function called when a new instance of this object is created. This
	# standard constructor supports three calling modes:
	#
	# 1. If called without parameters, simply creates a new, empty objects object
	# 2. If called with a single, valid primary key value, creates a new objects object and loads
	#    the record identified by the primary key value
	#
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		parent::__construct($pn_id);	# call superclass constructor
	}
	# ------------------------------------------------------
	public function insert($pa_options=null) {
		if (!trim($this->get('surname')) && !trim($this->get('forename'))) {
			// auto-split entity name if displayname is set
			$we_set_displayname = false;
			if($vs_display_name = trim($this->get('displayname'))) {
			
				if (($t_entity = caGetOption('subject', $pa_options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG')) {
					$va_label = [
						'displayname' => $vs_display_name,
						'surname' => $vs_display_name,
						'forename' => ''	
					];
				} else {
					$va_label = DataMigrationUtils::splitEntityName($vs_display_name, $pa_options);
					$we_set_displayname = true;
				}
				if(is_array($va_label)) {
					if (!$we_set_displayname) { unset($va_label['displayname']); } // just make sure we don't mangle the user-entered displayname

					foreach($va_label as $vs_fld => $vs_val) {
						$this->set($vs_fld, $vs_val);
					}
				} else {
					$this->postError(1100, _t('Something went wrong when splitting displayname'), 'ca_entity_labels->insert()');
					return false;
				}
			} else {
				$this->postError(1100, _t('Surname, forename or displayname must be set'), 'ca_entity_labels->insert()');
				return false;
			}
		}
		if (($t_entity = caGetOption('subject', $pa_options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG')) {
			$this->set('displayname', $this->get('surname'));
		} elseif (!$this->get('displayname')) {
			$this->set('displayname', trim(preg_replace('![ ]+!', ' ', $this->get('forename').' '.$this->get('middlename').' '.$this->get('surname'))));
		}
		
		return parent::insert($pa_options);
	}
	# ------------------------------------------------------
	public function update($pa_options=null) {
		if (!trim($this->get('surname')) && !trim($this->get('forename'))) {
			$this->postError(1100, _t('Surname or forename must be set'), 'ca_entity_labels->insert()');
			return false;
		}
		if (($t_entity = caGetOption('subject', $pa_options, null)) && ($t_entity->getTypeSetting('entity_class') == 'ORG')) {
			$this->set('displayname', $this->get('surname'));
		} elseif (!$this->get('displayname')) {
			$this->set('displayname', trim(preg_replace('![ ]+!', ' ', $this->get('forename').' '.$this->get('middlename').' '.$this->get('surname'))));
		}
		return parent::update($pa_options);
	}
	# -------------------------------------------------------
	/**
	 * Returns version of label 'display' field value suitable for sorting
	 * The sortable value is the same as the display value except when the display value
	 * starts with a definite article ('the' in English) or indefinite article ('a' or 'an' in English)
	 * in the locale of the label, in which case the article is moved to the end of the sortable value.
	 * 
	 * What constitutes an article is defined in the TimeExpressionParser localization files. So if the
	 * locale of the label doesn't correspond to an existing TimeExpressionParser localization, then
	 * the users' current locale setting is used.
	 */
	protected function _generateSortableValue() {
		if ($vs_sort_field = $this->getProperty('LABEL_SORT_FIELD')) {
			$vs_display_field = $this->getProperty('LABEL_DISPLAY_FIELD');
			
			// is entity org?
			$is_org = false;
			if (($entity = $this->getSubjectTableInstance()) && ($et = $entity->getTypeInstance())) {
				$is_org = ($et->getSetting('entity_class') === 'ORG');
			}
			if($is_org) {
				parent::_generateSortableValue();
			} else {
				$n = DataMigrationUtils::splitEntityName($this->get($vs_display_field), ['displaynameFormat' => 'surnamecommaforename']);
				$n = $n['displayname'];
				$this->set($vs_sort_field, $n);
			}
		}
	}
	# ------------------------------------------------------
}
