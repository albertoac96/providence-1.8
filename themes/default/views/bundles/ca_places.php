<?php
/* ----------------------------------------------------------------------
 * bundles/ca_places.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2020 Whirl-i-Gig
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
 
	AssetLoadManager::register('hierBrowser');
 
	$vs_id_prefix 		= $this->getVar('placement_code').$this->getVar('id_prefix');
	$t_instance 		= $this->getVar('t_instance');
	$t_item 			= $this->getVar('t_item');				// place
	$t_item_rel 		= $this->getVar('t_item_rel');
	$t_subject 			= $this->getVar('t_subject');
	$va_settings 		= $this->getVar('settings');
	$vs_add_label 		= $this->getVar('add_label');
	$va_rel_types		= $this->getVar('relationship_types');
	$vs_placement_code 	= $this->getVar('placement_code');
	$vn_placement_id	= (int)$va_settings['placement_id'];
	$vb_batch			= $this->getVar('batch');
	
	$vs_color 			= 	((isset($va_settings['colorItem']) && $va_settings['colorItem'])) ? $va_settings['colorItem'] : '';
	$vs_first_color 	= 	((isset($va_settings['colorFirstItem']) && $va_settings['colorFirstItem'])) ? $va_settings['colorFirstItem'] : '';
	$vs_last_color 		= 	((isset($va_settings['colorLastItem']) && $va_settings['colorLastItem'])) ? $va_settings['colorLastItem'] : '';
	
	$vs_sort			=	((isset($va_settings['sort']) && $va_settings['sort'])) ? $va_settings['sort'] : '';
	$vb_read_only		=	((isset($va_settings['readonly']) && $va_settings['readonly'])  || ($this->request->user->getBundleAccessLevel($t_instance->tableName(), 'ca_places') == __CA_BUNDLE_ACCESS_READONLY__));
	$vb_dont_show_del	=	((isset($va_settings['dontShowDeleteButton']) && $va_settings['dontShowDeleteButton'])) ? true : false;
	
	$va_initial_values	= $this->getVar('initialValues');
	
	$vb_quick_add_enabled = $this->getVar('quickadd_enabled');
	
	// Dyamically loaded sort ordering
	$loaded_sort 			= $this->getVar('sort');
	$loaded_sort_direction 	= $this->getVar('sortDirection');
	
	// params to pass during occurrence lookup
	$va_lookup_params = array(
		'types' => isset($va_settings['restrict_to_types']) ? $va_settings['restrict_to_types'] : (isset($va_settings['restrict_to_type']) ? $va_settings['restrict_to_type'] : ''),
		'noSubtypes' => (int)$va_settings['dont_include_subtypes_in_type_restriction'],
		'noInline' => (!$vb_quick_add_enabled || (bool) preg_match("/QuickAdd$/", $this->request->getController())) ? 1 : 0,
		'self' => $t_instance->tableName().':'.$t_instance->getPrimaryKey()
	);

	$count = $this->getVar('relationship_count');
	$num_per_page = caGetOption('numPerPage', $va_settings, 10);
	
	if (!RequestHTTP::isAjax()) {
		if(caGetOption('showCount', $va_settings, false)) { print $count ? "({$count})" : ''; }
	
		if ($vb_batch) {
			print caBatchEditorRelationshipModeControl($t_item, $vs_id_prefix);
		} else {
			print caEditorBundleShowHideControl($this->request, $vs_id_prefix, $va_settings, caInitialValuesArrayHasValue($vs_id_prefix, $this->getVar('initialValues')));
		}
		print caEditorBundleMetadataDictionary($this->request, $vs_id_prefix, $va_settings);
	}
	
	$va_errors = array();
	foreach($va_action_errors = $this->request->getActionErrors($vs_placement_code) as $o_error) {
		$va_errors[] = $o_error->getErrorDescription();
	}
?>
<div id="<?php print $vs_id_prefix; ?>" <?php print $vb_batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	print "<div class='bundleSubLabel'>";	
	if(is_array($this->getVar('initialValues')) && sizeof($this->getVar('initialValues'))) {
		print caEditorBundleBatchEditorControls($this->request, $vn_placement_id, $t_subject, $t_instance->tableName(), $va_settings);
		print caGetPrintFormatsListAsHTMLForRelatedBundles($vs_id_prefix, $this->request, $t_instance, $t_item, $t_item_rel, $vn_placement_id);
	
		if(!$vb_read_only) {
			print caEditorBundleSortControls($this->request, $vs_id_prefix, $t_item->tableName(), $t_instance->tableName(), array_merge($va_settings, ['sort' => $loaded_sort, 'sortDirection' => $loaded_sort_direction]));
		}
	}
	print "<div style='clear:both;'></div></div><!-- end bundleSubLabel -->";

	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
<?php
	switch($va_settings['list_format']) {
		case 'list':
?>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo listRel caRelatedItem">
<?php
	if (!$vb_read_only && ca_editor_uis::loadDefaultUI($t_item_rel->tableNum(), $this->request)) {
?><a href="#" class="caInterstitialEditButton listRelEditButton"><?php print caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "18px"); ?></a><?php
	}
	if (!$vb_read_only && !$vb_dont_show_del) {
?><a href="#" class="caDeleteItemButton listRelDeleteButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a><?php
	}
?>
			<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_occurrences', '{occurrence_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"></a>
			<span id='<?php print $vs_id_prefix; ?>_BundleTemplateDisplay{n}'>
<?php
			print caGetRelationDisplayString($this->request, 'ca_places', array('class' => 'caEditItemButton', 'id' => "{$vs_id_prefix}_edit_related_{n}"), array('display' => '_display', 'makeLink' => true, 'prefix' => $vs_id_prefix));
?>
			</span>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
		</div>
<?php
			break;
		case 'bubbles':
		default:
?>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo roundedRel caRelatedItem">
			<span id='<?php print $vs_id_prefix; ?>_BundleTemplateDisplay{n}'>
<?php
			print caGetRelationDisplayString($this->request, 'ca_places', array('class' => 'caEditItemButton', 'id' => "{$vs_id_prefix}_edit_related_{n}"), array('display' => '_display', 'makeLink' => true, 'prefix' => $vs_id_prefix));
?>
			</span>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
<?php
	if (!$vb_read_only && ca_editor_uis::loadDefaultUI($t_item_rel->tableNum(), $this->request)) {
?><a href="#" class="caInterstitialEditButton listRelEditButton"><?php print caNavIcon(__CA_NAV_ICON_INTERSTITIAL_EDIT_BUNDLE__, "16px"); ?></a><?php
	}
	if (!$vb_read_only && !$vb_dont_show_del) {
?><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a><?php
	}
?>			
			<div style="display: none;" class="itemName">{label}</div>
			<div style="display: none;" class="itemIdno">{idno_sort}</div>
		</div>
<?php
	}
?>
	</textarea>
<?php
	//
	// Template to generate controls for creating new relationship
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo caRelatedItem">
<?php
	if (!(bool)$va_settings['useHierarchicalBrowser']) {
?>
			<table class="caListItem">
				<tr>
					<td>
						<input type="text" size="60" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{{label}}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
					</td>
					<td>
						<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
						<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
					</td>
					<td>
						<a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
						
						<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_places', '{place_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}"><?php print caNavIcon(__CA_NAV_ICON_GO__, 1); ?></a>
					</td>
				</tr>
			</table>
<?php
} else {
		$vn_use_as_root_id = 'null';
		if (is_array($va_settings['restrict_to_lists']) && (sizeof($va_settings['restrict_to_lists']) == 1)) {
			$t_item = new ca_list_items();
			if ($t_item->load(array('list_id' => $va_settings['restrict_to_lists'][0], 'parent_id' => null))) {
				$vn_use_as_root_id = $t_item->getPrimaryKey();
			}
		} 
		
		if (!$vb_read_only && !$vb_dont_show_del) {
?>
			<div style="float: right;"><a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a></div>
<?php
	}
?>
			<div style='width: 690px; height: <?php print $va_settings['hierarchicalBrowserHeight']; ?>;'>
				
				<div id='<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}' style='width: 100%; height: 100%;' class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->	</div>
				
			<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
			<div style="float: right;">
				<div class='hierarchyBrowserSearchBar'><?php print _t('Search'); ?>: <input type='text' id='<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}' class='hierarchyBrowserSearchBar' name='search' value='' size='40'/></div>
			</div>
			<div style="float: left;" class="hierarchyBrowserCurrentSelectionText">
				<select name="<?php print $vs_id_prefix; ?>_type_id{n}" id="<?php print $vs_id_prefix; ?>_type_id{n}" style="display: none;"></select>
				<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
				
				<span class="hierarchyBrowserCurrentSelectionText" id="<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}"> </span>
			</div>	
			
			<script type='text/javascript'>
				jQuery(document).ready(function() { 
					var <?php print $vs_id_prefix; ?>oHierBrowser{n} = caUI.initHierBrowser('<?php print $vs_id_prefix; ?>_hierarchyBrowser{n}', {
						uiStyle: 'horizontal',
						levelDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'GetHierarchyLevel', array()); ?>',
						initDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'GetHierarchyAncestorList'); ?>',
						
						selectOnLoad : true,
						browserWidth: "<?php print $va_settings['hierarchicalBrowserWidth']; ?>",
						
						dontAllowEditForFirstLevel: false,
						
						className: 'hierarchyBrowserLevel',
						classNameContainer: 'hierarchyBrowserContainer',
						
						editButtonIcon: "<?php print caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						
						//initItemID: <?php print (int)Session::getVar('ca_places_browse_last_id'); ?>,
						useAsRootID: <?php print $vn_use_as_root_id; ?>,
						indicator: "<?php print caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
						
						displayCurrentSelectionOnLoad: false,
						currentSelectionDisplayID: '<?php print $vs_id_prefix; ?>_browseCurrentSelectionText{n}',
						onSelection: function(item_id, parent_id, name, display, type_id) {
							caRelationBundle<?php print $vs_id_prefix; ?>.select('{n}', {id: item_id, type_id: type_id}, display);
						}
					});
					
					jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').autocomplete(
						{
							source: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'Get', array('noInline' => 1)); ?>',
							minLength: <?= (int)$t_subject->getAppConfig()->get(["ca_entities_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>, delay: 800, html: true,
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									<?php print $vs_id_prefix; ?>oHierBrowser{n}.setUpHierarchy(ui.item.id);	// jump browser to selected item
								}
								event.preventDefault();
								jQuery('#<?php print $vs_id_prefix; ?>_hierarchyBrowserSearch{n}').val('');
							}
						}
					);
				});
			</script>
		</div>
<?php
	}
?>
		</div>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caItemList">
<?php
	if (sizeof($va_errors)) {
?>
		<span class="formLabelError"><?php print join("; ", $va_errors); ?><br class="clear"/></span>
<?php
	}
?>
		
		</div>
		<div class="caNewItemList"></div>
		<input type="hidden" name="<?php print $vs_id_prefix; ?>BundleList" id="<?php print $vs_id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
<?php
	if (!$vb_read_only) {
?>	
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?php print $vs_add_label ? $vs_add_label : _t("Add relationship"); ?></a></div>
<?php
	}
?>
	</div>
</div>

<?php if($vb_quick_add_enabled) { ?>
<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Quick Add', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
</div>
<?php } ?>
<div id="caRelationEditorPanel<?php print $vs_id_prefix; ?>" class="caRelationQuickAddPanel"> 
	<div id="caRelationEditorPanel<?php print $vs_id_prefix; ?>ContentArea">
	<div class='dialogHeader'><?php print _t('Relation editor', $t_item->getProperty('NAME_SINGULAR')); ?></div>
		
	</div>
	
	<textarea class='caBundleDisplayTemplate' style='display: none;'>
		<?php print caGetRelationDisplayString($this->request, 'ca_places', array(), array('display' => '_display', 'makeLink' => false)); ?>
	</textarea>
</div>	
	
<script type="text/javascript">
<?php if($vb_quick_add_enabled) { ?>
	var caRelationQuickAddPanel<?php print $vs_id_prefix; ?>;
<?php } ?>	
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControlTrigger').click(function() { jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideToggle(200); return false; });
		jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls a.caItemListSortControl').click(function() {jQuery('#<?php print $vs_id_prefix; ?>caItemListSortControls').slideUp(200); return false; });
		
		if (caUI.initPanel) {
<?php if($vb_quick_add_enabled) { ?>
			caRelationQuickAddPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationQuickAddPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
<?php } ?>
			caRelationEditorPanel<?php print $vs_id_prefix; ?> = caUI.initPanel({ 
				panelID: "caRelationEditorPanel<?php print $vs_id_prefix; ?>",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRelationEditorPanel<?php print $vs_id_prefix; ?>ContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
				jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['label', 'id', 'type_id', 'typename', 'idno', 'label', 'idno_sort'],
			initialValues: <?php print json_encode($va_initial_values); ?>,
			initialValueOrder: <?php print json_encode(array_keys($va_initial_values)); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			placementID: '<?php print $vn_placement_id; ?>',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			newItemListClassName: 'caNewItemList',
			listItemClassName: 'caRelatedItem',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			hideOnNewIDList: ['<?php print $vs_id_prefix; ?>_edit_related_'],
			showEmptyFormsOnLoad: 1,
			minChars: <?= (int)$t_subject->getAppConfig()->get(["ca_places_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>,
			relationshipTypes: <?php print json_encode($this->getVar('relationship_types_by_sub_type')); ?>,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'Get', $va_lookup_params); ?>',
			types: <?php print json_encode($va_settings['restrict_to_types']); ?>,
			restrictToAccessPoint: <?php print json_encode($va_settings['restrict_to_access_point']); ?>,
			restrictToSearch: <?php print json_encode($va_settings['restrict_to_search']); ?>,
			bundlePreview: <?php print caGetBundlePreviewForRelationshipBundle($this->getVar('initialValues')); ?>,
			readonly: <?php print $vb_read_only ? "true" : "false"; ?>,
			isSortable: <?php print ($vb_read_only || $vs_sort) ? "false" : "true"; ?>,
			listSortOrderID: '<?php print $vs_id_prefix; ?>BundleList',
			listSortItems: 'div.roundedRel,div.listRel',
			autocompleteInputID: '<?php print $vs_id_prefix; ?>_autocomplete',
<?php if($vb_quick_add_enabled) { ?>
			quickaddPanel: caRelationQuickAddPanel<?php print $vs_id_prefix; ?>,
			quickaddUrl: '<?php print caNavUrl($this->request, 'editor/places', 'PlaceQuickAdd', 'Form', array('place_id' => 0, 'dont_include_subtypes_in_type_restriction' => (int)$va_settings['dont_include_subtypes_in_type_restriction'], 'prepopulate_fields' => join(";", $va_settings['prepopulateQuickaddFields']))); ?>',
<?php } ?>
			sortUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Sort', array('table' => $t_item_rel->tableName())); ?>',
			
			loadedSort: <?= json_encode($loaded_sort); ?>,
			loadedSortDirection: <?= json_encode($loaded_sort_direction); ?>,
			
			interstitialButtonClassName: 'caInterstitialEditButton',
			interstitialPanel: caRelationEditorPanel<?php print $vs_id_prefix; ?>,
			interstitialUrl: '<?php print caNavUrl($this->request, 'editor', 'Interstitial', 'Form', array('t' => $t_item_rel->tableName())); ?>',
			interstitialPrimaryTable: '<?php print $t_instance->tableName(); ?>',
			interstitialPrimaryID: <?php print (int)$t_instance->getPrimaryKey(); ?>,
			
			itemColor: '<?php print $vs_color; ?>',
			firstItemColor: '<?php print $vs_first_color; ?>',
			lastItemColor: '<?php print $vs_last_color; ?>',
			
			totalValueCount: <?php print (int)$count; ?>,
			partialLoadUrl: '<?php print caNavUrl($this->request, '*', '*', 'loadBundleValues', array($t_subject->primaryKey() => $t_subject->getPrimaryKey(), 'placement_id' => $vn_placement_id, 'bundle' => 'ca_places')); ?>',
			partialLoadIndicator: '<?php print addslashes(caBusyIndicatorIcon($this->request)); ?>',
			loadSize: <?php print $num_per_page; ?>,
			
			minRepeats: <?php print caGetOption('minRelationshipsPerRow', $va_settings, 0); ?>,
			maxRepeats: <?php print caGetOption('maxRelationshipsPerRow', $va_settings, 65535); ?>
		});
	});
</script>
