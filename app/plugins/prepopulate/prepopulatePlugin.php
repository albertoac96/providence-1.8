<?php
/* ----------------------------------------------------------------------
 * prepopulatePlugin.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2020 Whirl-i-Gig
 * This file originally contributed 2014 by Gaia Resources
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
 * ----------------------------------------------------------------------
 */

require_once(__CA_APP_DIR__."/plugins/prepopulate/lib/applyPrepopulateRulesTool.php");

class prepopulatePlugin extends BaseApplicationPlugin {
	# --------------------------------------------------------------------------------------------
	/**
	 * Plugin config
	 * @var Configuration
	 */
	var $opo_plugin_config = null;
	# --------------------------------------------------------------------------------------------
	public function __construct($ps_plugin_path) {
		$this->description = _t('This plugin allows prepopulating field values based on display templates. See http://docs.collectiveaccess.org/wiki/Prepopulate for more info.');
		parent::__construct();

		$this->opo_plugin_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'prepopulate.conf');
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * 
	 */
	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => (bool) $this->opo_plugin_config->get('enabled')
		);
	}
	# --------------------------------------------------------------------------------------------
	public function hookInsertItem(&$pa_params) {
		if($this->opo_plugin_config->get('enabled') && !caGetOption('for_duplication', $pa_params, false)) {
			$this->prepopulateFields($pa_params['instance'], ['hook' => 'save']);
		}
		return true;
	}
	public function hookUpdateItem(&$pa_params) {
		if($this->opo_plugin_config->get('enabled') && !caGetOption('for_duplication', $pa_params, false)) {
			$this->prepopulateFields($pa_params['instance'], ['hook' => 'save']);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function hookSaveItem(&$pa_params) {
		if($this->opo_plugin_config->get('enabled') && !caGetOption('for_duplication', $pa_params, false)) {
			$this->prepopulateFields($pa_params['instance'], ['hook' => 'save']);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function hookEditItem(&$pa_params) {
		if ($this->opo_plugin_config->get('enabled') && !caGetOption('for_duplication', $pa_params, false)) {
			$this->prepopulateFields($pa_params['instance'], ['hook' => 'edit']);
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	public function hookCLICaUtilsGetCommands(&$pa_params) {
	    $pa_params['Maintenance']['apply_prepopulate_rules'] = [
	    	'Command' => 'apply-prepopulate-rules',
	        'Options' => [],
	        'Help' => _t('Applies rules defined in prepopulate.conf to all relevant records.'),
	        'ShortHelp' => _t('Applies rules defined in prepopulate.conf to all relevant records.'),
	    ];
	    return $pa_params;
	}
	# -------------------------------------------------------
    /**
     * Run commands from CLI caUtils
     */
    public function hookCLICaUtilsGetToolWithSettings(&$pa_params) {
        $tool = new applyPrepopulateRulesTool(['prepopulateInstance' => $this]);
        $tool->setSettings($pa_params[1]);
        $tool->setMode($pa_params[2]);
        
        $pa_params['tool'] = $tool;
        return $pa_params;
    }
	# --------------------------------------------------------------------------------------------
	/**
	 * Prepopulate record fields according to rules in prepopulate.conf
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_instance The table instance to prepopulate
	 * @param array $pa_options Options array. Available options are:
	 * 		prepopulateConfig = override path to prepopulate.conf, e.g. for testing purposes
	 *		hook = indicates what triggered application: "save" or "edit"
	 * @return bool success or not
	 */
	public function prepopulateFields(&$t_instance, $pa_options=null) {
		if(!$t_instance->getPrimaryKey()) { return false; }
		if($vs_prepopulate_cfg = caGetOption('prepopulateConfig', $pa_options, null)) {
			$this->opo_plugin_config = Configuration::load($vs_prepopulate_cfg);
		}
		
		$hook = caGetOption('hook', $pa_options, null);

		$default_prepop_on_save  = (bool)$this->opo_plugin_config->get('prepopulate_fields_on_save');
		$default_prepop_on_edit  = (bool)$this->opo_plugin_config->get('prepopulate_fields_on_edit');

		$va_rules = $this->opo_plugin_config->get('prepopulate_rules');
		if(!$va_rules || (!is_array($va_rules)) || (sizeof($va_rules)<1)) { return false; }

		global $g_ui_locale_id;

		// we need to unset the form timestamp to disable the 'Changes have been made since you loaded this data' warning when we update() $this
		// the warning makes sense because an update()/insert() is called before we arrive here but after the form_timestamp ... but we chose to ignore it
		//$vn_timestamp = $_REQUEST['form_timestamp'];
		//unset($_REQUEST['form_timestamp']);

		$vb_we_set_transaction = false;
		if (!$t_instance->inTransaction()) {
			$t_instance->setTransaction(new Transaction($t_instance->getDb()));
			$vb_we_set_transaction = true;
		}

		// process rules
		$va_expression_vars = array(); // we only process those if and when we need them
		foreach($va_rules as $vs_rule_key => $va_rule) {
			if($t_instance->tableName() != $va_rule['table']) { continue; }
			$useFor = caGetOption('useFor', $va_rule, null);
			if ($useFor && !is_array($useFor)) { $useFor = [$useFor]; }
			$useFor = is_array($useFor) ? array_map(function($v) { return strtolower($v); }, $useFor) : null;
			
			if (is_array($useFor) && !in_array($hook, $useFor, true)) { 
				continue; 
			} elseif(!$useFor) {
				if (($hook === 'edit') && !$default_prepop_on_edit) { continue; }
				if (($hook === 'save') && !$default_prepop_on_save) { continue; }
			}

			$vs_mode = strtolower(caGetOption('mode', $va_rule, 'merge'));
			
			// check target
			$vs_target = caGetOption('target', $va_rule, null);
			
			if(strlen($vs_target)<1) { Debug::msg("[prepopulateFields()] skipping rule $vs_rule_key because target is not set"); continue; }
			
			$vb_is_relationship_rule = Datamodel::tableExists($vs_target);
            if (!$vb_is_relationship_rule) {
            	$vs_source = caGetOption('source', $va_rule, null);
                
                // check template
                $vs_template = caGetOption('template', $va_rule, null);
                if((strlen($vs_template) < 1) && (strlen($vs_source = caGetOption('source', $va_rule, null)) < 1)) { Debug::msg("[prepopulateFields()] skipping rule $vs_rule_key because template is not set"); continue; }
            }
            
            $vs_context = caGetOption('context', $va_rule, null);

			// respect restrictToTypes option
			if($va_rule['restrictToTypes'] && is_array($va_rule['restrictToTypes']) && (sizeof($va_rule['restrictToTypes']) > 0)) {
				if(!in_array($t_instance->getTypeCode(), $va_rule['restrictToTypes'])) {
					Debug::msg("[prepopulateFields()] skipping rule $vs_rule_key because current record type ".$t_instance->getTypeCode()." is not in restrictToTypes");
					continue;
				}
			}

			// skip this rule if expression is true
			if($va_rule['skipIfExpression'] && (strlen($va_rule['skipIfExpression'])>0)) {
				$va_tags = caGetTemplateTags($va_rule['skipIfExpression']);

				foreach($va_tags as $vs_tag) {
					if(!isset($va_expression_vars[$vs_tag])) {
						$va_expression_vars[$vs_tag] = $t_instance->get($vs_tag, array('returnIdno' => true, 'delimiter' => ';'));
					}
				}

				if(ExpressionParser::evaluate($va_rule['skipIfExpression'], $va_expression_vars)) {
					Debug::msg("[prepopulateFields()] skipping rule $vs_rule_key because skipIfExpression evaluated true");
					continue;
				}
			}

            if (!$vb_is_relationship_rule) {
                // evaluate template
                $vs_value = caProcessTemplateForIDs($vs_template, $t_instance->tableNum(), array($t_instance->getPrimaryKey()), array('path' => true));
                Debug::msg("[prepopulateFields()] processed template for rule $vs_rule_key value is: ".$vs_value);
            }

			// inject into target
			$va_parts = explode('.', $vs_target);
			
			if ((sizeof($va_parts) == 1) && $vb_is_relationship_rule) {    // clone relationships
			    if (($vs_mode === 'addifempty') && $t_instance->hasRelationshipsWith($vs_target)) { 
			        Debug::msg("[prepopulateFields()] skipped rule {$vs_rule_key} because mode is addIfEmpty and it already has {$vs_target} relationships.");
			        continue;
			    }
			    
			    $va_rels = null;
			    $va_instance_rel_ids = [];
			    
                $va_restrict_to_relationship_types = caGetOption('restrictToRelationshipTypes', $va_rule, null);
                $va_exclude_relationship_types = caGetOption('excludeRelationshipTypes', $va_rule, null);
                $va_restrict_to_related_types = caGetOption('restrictToRelatedTypes', $va_rule, null);
                $va_exclude_related_types = caGetOption('excludeRelatedTypes', $va_rule, null);
			    
			    switch($vs_context) {
			        case 'parent':
			            $t_parent = Datamodel::getInstance($t_instance->tableName());
			            if (($vn_parent_id = $t_instance->get($t_instance->getProperty('HIERARCHY_PARENT_ID_FLD'))) && $t_parent->load($vn_parent_id)) {
			            
			                $va_rels = $t_parent->getRelatedItems($vs_target, ['showCurrentOnly' => caGetOption('currentOnly', $va_rule, false)]);
			            }
			            break;
			         case 'children':
			            if($t_instance->getPrimaryKey()) {
                            $va_child_ids = $t_instance->getHierarchy($t_instance->getPrimaryKey(), ['idsOnly' => true]);
                            if (is_array($va_child_ids) && sizeof($va_child_ids)) {
                                 $va_rels = $t_parent->getRelatedItems($vs_target, ['row_ids' => $va_child_ids, 'showCurrentOnly' => caGetOption('currentOnly', $va_rule, false)]);
                            }
                        }
			            break;
			         case 'related':
			            if($t_instance->getPrimaryKey()) {
                            $va_rel_ids = $t_instance->get($t_instance->tableName().'.related.'.$t_instance->primaryKey(), ['idsOnly' => true, 'returnAsArray' => true]);
                
                            if (is_array($va_rel_ids) && sizeof($va_rel_ids)) {
                                 $va_rels = $t_parent->getRelatedItems($vs_target, ['row_ids' => $va_rel_ids, 'showCurrentOnly' => caGetOption('currentOnly', $va_rule, false)]);
                            }
                        }
			            break;
			    }
			    
			    if (is_array($va_rels)) {
                    foreach($va_rels as $va_rel) {
                        if (is_array($va_restrict_to_relationship_types) && sizeof($va_restrict_to_relationship_types) && !in_array($va_rel['relationship_type_code'], $va_restrict_to_relationship_types)) { continue; }
                        if (is_array($va_exclude_relationship_types) && sizeof($va_exclude_relationship_types) && in_array($va_rel['relationship_type_code'], $va_exclude_relationship_types)) { continue; }

                        $va_related_types = caMakeTypeList($vs_target, [$va_rel['item_type_id']]);
                        if (is_array($va_restrict_to_related_types) && sizeof($va_restrict_to_related_types) && sizeof(array_intersect($va_related_types, $va_restrict_to_related_types))) { continue; }
                        if (is_array($va_exclude_related_types) && sizeof($va_exclude_related_types) && !sizeof(array_intersect($va_related_types, $va_exclude_related_types))) { continue; }
                
                        $vn_target_id = $va_rel[Datamodel::primaryKey($vs_target)];
                        if (!($va_existing_rel_ids = $t_instance->relationshipExists($vs_target, $vn_target_id, $va_rel['relationship_type_code'], $va_rel['effective_date']))) {
                            if ($t = $t_instance->addRelationship($vs_target, $vn_target_id, $va_rel['relationship_type_code'], $va_rel['effective_date'])) {
                                $va_instance_rel_ids[] = $t->getPrimaryKey();
                            } else {
                                Debug::msg("[prepopulateFields()] could not add {$vs_target} relationship");
                            }
                        } else {
                            $va_instance_rel_ids = array_merge($va_instance_rel_ids, $va_existing_rel_ids);
                        }
                    }
                
                    if ($vs_mode === 'overwrite') {
                        // remove rels that aren't in target
                        if (is_array($va_instance_rels = $t_instance->getRelatedItems($vs_target))) {
                            foreach($va_instance_rels as $va_instance_rel) {
                                if (!in_array($va_instance_rel['relation_id'], $va_instance_rel_ids)) {
                                    if (!$t_instance->removeRelationship($vs_target, $va_instance_rel['relation_id'])) {
                                        Debug::msg("[prepopulateFields()] could not delete {$vs_target} relationship in overwrite mode");
                                    }
                                }  
                            }
                        }
                    }
                }
// intrinsic or simple (non-container) attribute
			} elseif(sizeof($va_parts) == 2) {
// intrinsic
				if($t_instance->hasField($va_parts[1])) {
					switch($vs_mode) {
						case 'overwrite': // always set
							$t_instance->set($va_parts[1], $vs_value);
							break;
						case 'overwriteifset': // set if value is not empty
						    if(strlen($vs_value) > 0) {
							    $t_instance->set($va_parts[1], $vs_value);
							}
							break;
						case 'addifempty':
						default:
							if(!$t_instance->get($va_parts[1])) {
								$t_instance->set($va_parts[1], $vs_value);
							} else {
								Debug::msg("[prepopulateFields()] rule {$vs_rule_key}: intrinsic skipped because it already has value and mode is addIfEmpty or merge");
							}
							break;
					}
// attribute/element
				} elseif($t_instance->hasElement($va_parts[1])) {
					$datatype = ca_metadata_elements::getElementDatatype($va_parts[1]);

					$va_attributes = $t_instance->getAttributesByElement($va_parts[1]);
					
					$va_source_map = caGetOption('sourceMap', $va_rule, null);
					
					if (($datatype == 0) && $vs_source) { // full container clone using "source" rather than template
						if (is_array($source_values = $t_instance->get($vs_source, ['returnWithStructure' => true]))) {
						
							$isset = false;
							foreach($va_attributes as $attr) {
								foreach($attr->getValues() as $v) {
									if(is_array($va_source_map) && sizeof($va_source_map) && !array_key_exists($v->getElementCode(), $va_source_map)) { continue; }
									
									if (strlen($v->getDisplayValue()) > 0) { $isset = true; break(2); }
								}
							}
							if (($vs_mode == 'addifempty') && $isset) { continue; }
							if (($vs_mode == 'overwriteifset') && !$isset) { continue; }
							
							$i = 0;
							$t_instance->removeAttributes($va_parts[1]);
							$t_instance->update(['force' => true, 'hooks' => false]);
							
							if($t_instance->numErrors()) { 
								Debug::msg(_t("[prepopulateFields()] error while removing old values during copy of containers: %1", join("; ", $t_instance->getErrors())));
							}
		
							foreach(array_shift($source_values) as $attr_id => $attr) {
								if(is_array($va_source_map) && sizeof($va_source_map)) {
									$map_attr = [];
									foreach($va_source_map as $sk => $sv) {
										$map_attr[$sv] = $attr[$sk];
									}
									$attr = $map_attr;
								}
								if ($i == 0) {
									$t_instance->replaceAttribute($attr, $va_parts[1]);
								} else {
									$t_instance->addAttribute($attr, $va_parts[1]);
								}
								if($t_instance->numErrors()) { 
									Debug::msg(_t("[prepopulateFields()] error during copy of containers: %1", join("; ", $t_instance->getErrors())));
								}
								$i++;
							}
						}
					} else {
						if((sizeof($va_attributes)>1) && ($vs_mode !== 'addifempty')) {
							$t_instance->removeAttributes($va_parts[1]);
						}
						switch($vs_mode) {
							case 'overwrite': // always replace first value we find
								$t_instance->replaceAttribute(array(
									$va_parts[1] => $vs_value,
									'locale_id' => $g_ui_locale_id
								), $va_parts[1]);
								break;
							case 'overwriteifset': 
								if(strlen($vs_value) > 0) {
									$t_instance->replaceAttribute(array(
										$va_parts[1] => $vs_value,
										'locale_id' => $g_ui_locale_id
									), $va_parts[1]);
								}
								break;
							default:
							case 'addifempty': // only add value if none exists
								if(!$t_instance->get($vs_target)) {
									$t_instance->replaceAttribute(array(
										$va_parts[1] => $vs_value,
										'locale_id' => $g_ui_locale_id
									), $va_parts[1]);
								}
								break;
						}
					}
				}
// "container"
			} elseif(sizeof($va_parts)==3) {
// actual container
				if($t_instance->hasElement($va_parts[1])) {
					$va_attr = $t_instance->getAttributesByElement($va_parts[1]);
					switch (sizeof($va_attr)) {
						case 1:
							switch ($vs_mode) {
								case 'overwrite':
									$vo_attr = array_pop($va_attr);
									$va_value = array($va_parts[2] => $vs_value);

                                    $vb_is_set = false;
									foreach ($vo_attr->getValues() as $o_val) {
										if ($o_val->getElementCode() != $va_parts[2]) {
											$va_value[$o_val->getElementCode()] = $v = $o_val->getDisplayValue(['idsOnly' => true]);
											$vb_is_set = true; 
										}
									}
                                    if (($vs_mode === 'overwrite') || $vb_is_set) {
									    $t_instance->_editAttribute($vo_attr->getAttributeID(), $va_value, $t_instance->getTransaction());
									}
									break;
								case 'overwriteifset':
									$vo_attr = array_pop($va_attr);
									$va_value = array($va_parts[2] => $vs_value);

                                    $vb_is_set = false;
									foreach ($vo_attr->getValues() as $o_val) {
										if ($o_val->getElementCode() != $va_parts[2]) {
											if (strlen($v) > 0) { 
												$va_value[$o_val->getElementCode()] = $v = $o_val->getDisplayValue(['idsOnly' => true]);
												$vb_is_set = true; 
											}
										}
									}
                                    if (($vs_mode === 'overwrite') || $vb_is_set) {
									    $t_instance->_editAttribute($vo_attr->getAttributeID(), $va_value, $t_instance->getTransaction());
									}
									break;
								case 'addifempty':
									$vo_attr = array_pop($va_attr);
									$va_value = array($va_parts[2] => $vs_value);
									$vb_update = false;
									foreach ($vo_attr->getValues() as $o_val) {
										if ($o_val->getElementCode() != $va_parts[2]) {
											$va_value[$o_val->getElementCode()] = $o_val->getDisplayValue(['idsOnly' => true]);
										} elseif (!$o_val->getDisplayValue()) {
											$vb_update = true;
										}
									}

									if ($vb_update) {
										$t_instance->editAttribute($vo_attr->getAttributeID(), $va_parts[1], $va_value);
									}
									break;
								default:
									Debug::msg("[prepopulateFields()] unsupported mode {$vs_mode} for target bundle");
									break;
							}
							break;
						case 0: // if no container value exists, always add it (ignoring mode)
							$t_instance->addAttribute(array(
								$va_parts[2] => $vs_value,
								'locale_id' => $g_ui_locale_id
							), $va_parts[1]);
							break;
						default:
							Debug::msg("[prepopulateFields()] containers with multiple values are not supported");
							break;
					}
// labels
				} elseif($va_parts[1] == 'preferred_labels' || $va_parts[1] == 'nonpreferred_labels') {
					$vb_preferred = ($va_parts[1] == 'preferred_labels');
					if (!($t_label = Datamodel::getInstanceByTableName($t_instance->getLabelTableName(), true))) { continue; }
					if(!$t_label->hasField($va_parts[2])) { continue; }

					switch($t_instance->getLabelCount($vb_preferred)) {
						case 0: // if no value exists, always add it (ignoring mode)
							$t_instance->addLabel(array(
								$va_parts[2] => $vs_value,
							), $g_ui_locale_id, null, $vb_preferred);
							break;
						case 1:
							switch ($vs_mode) {
								case 'overwrite':
								case 'overwriteifset':
								case 'addifempty':
								    $va_labels = caExtractValuesByUserLocale($t_instance->getLabels(null, $vb_preferred ? __CA_LABEL_TYPE_PREFERRED__ : __CA_LABEL_TYPE_NONPREFERRED__));
									
									if (sizeof($va_labels)) {
										$va_labels = caExtractValuesByUserLocale($va_labels);
										$va_label = array_shift($va_labels);
										
										$label_fld = $t_instance->getLabelDisplayField();
							            $is_blank = (isset($va_label[$label_fld]) && ($va_label[$label_fld] == '['._t('BLANK').']'));
										
										$vb_update = false;
										if($vs_mode == 'overwrite') {
											$va_label[$va_parts[2]] = $vs_value;
											$vb_update = true;
										} elseif(($vs_mode == 'overwriteifset') && (strlen($vs_value) > 0))  {
                                            $va_label[$va_parts[2]] = $vs_value;
                                            $vb_update = true;
										} else {
										    $l = trim($va_label[$va_parts[2]]);
											if((strlen($l) == 0) || ($is_blank)) { // in addifempty mode only edit label when field is not set
												$va_label[$va_parts[2]] = $vs_value;
												$vb_update = true;
											}
										}

										if($vb_update) {
											unset($va_label['source_info']);
											$t_instance->editLabel(
												$va_label['label_id'], $va_label, $g_ui_locale_id, null, $vb_preferred
											);
										}
									} elseif (($vs_mode !== 'overwriteifset') || (strlen($vs_value) > 0)) {
										$t_instance->addLabel(array(
											$va_parts[2] => $vs_value,
										), $g_ui_locale_id, null, $vb_preferred);
									}
									break;
								default:
									Debug::msg("[prepopulateFields()] unsupported mode {$vs_mode} for target bundle");
									break;
							}
							break;
						default:
							Debug::msg("[prepopulateFields()] records with multiple labels are not supported");
							break;
					}
				}
			}
		}


		if (count($t_instance->getChangedFieldValuesArray()) > 0) {
			if(isset($_REQUEST['form_timestamp']) && ($_REQUEST['form_timestamp'] > 0)) { $_REQUEST['form_timestamp'] = time(); }
			$t_instance->update(['force' => true, 'hooks' => false]);
			if($t_instance->numErrors() > 0) {
				foreach($t_instance->getErrors() as $vs_error) {
					Debug::msg("[prepopulateFields()] there was an error while updating the record: ".$vs_error);
				}
				if ($vb_we_set_transaction) { $t_instance->removeTransaction(false); }
				return false;
			}
		}

		if ($vb_we_set_transaction) { $t_instance->removeTransaction(true); }
		return true;
	}
	# --------------------------------------------------------------------------------------------
}
