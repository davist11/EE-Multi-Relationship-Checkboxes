<?php

/***************************************************************************************************
****  Multi Relationship Checkboxes  ***************************************************************

****  EDITED FOR CHECKBOXES BY DOUG AVERY AND TREVOR DAVIS, VIGET LABS *****************************

    Select multiple entries to link to using checkboxes instead of a multi-select. Based on v1.0.6
		
		This will break in PHP4 because call_user_func_array does not support passing by reference,
		at least as far as I can tell.  To get around this, open your /system/modules/weblog/mod.weblog.php
		file and find this line
			
			// -------------------------------------------
			// 'weblog_entries_tagdata' hook.
		
		to this line
			
			//
			// -------------------------------------------
		
		replace the inside with this
		
			if (isset($EXT->extensions['weblog_entries_tagdata']))
			{
				// -- PHP4 Fix For call_user_func_array not passing by reference
				global $Weblog; $Weblog = $this;
				// -- End Fix
				
				$tagdata = $EXT->call_extension('weblog_entries_tagdata', $tagdata, $row, $this);
				if ($EXT->end_script === TRUE) return $tagdata;
				
				// -- PHP4 Fix For call_user_func_array not passing by reference
				$this = $Weblog; unset($Weblog);
				// -- End Fix
			}
		
        INFO
            Developed by: Mark Huot
            Date: 2006-10-04
..................................................................................................*/

if ( ! defined('EXT')) {
    exit('Invalid file request'); }


class multi_relationship_checkboxes {
	var $settings		= array();
	
	var $name			= 'Multi Relationship Checkboxes';
	var $type			= 'mrelcheck';
	var $version		= '1.0.6';
	var $description	= 'Allows a one to many relationship link with checkboxes';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://docs.markhuot.com';
	
	//
	// Constructor
	//
	function custom_field($settings='')
	{
		$this->settings = $settings;
	}
	
	
	//
	// Add to Database
	//
	function activate_extension ()
	{
		global $DB;
		
		// -- Add edit_field_groups
		$DB->query(
			$DB->insert_string('exp_extensions', 
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'edit_field_groups',
					  'hook'         => 'show_full_control_panel_end',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
		
		// -- Add edit_custom_field
		$DB->query(
			$DB->insert_string('exp_extensions', 
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'edit_custom_field',
					  'hook'         => 'publish_admin_edit_field_extra_row',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
		
		// -- Add publish
		$DB->query(
			$DB->insert_string('exp_extensions',
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'publish',
					  'hook'         => 'publish_form_field_unique',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
		
		// -- Add modify_post
		$DB->query(
			$DB->insert_string('exp_extensions',
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'modify_post',
					  'hook'         => 'submit_new_entry_start',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
		
		// -- Add submit_relation
		$DB->query(
			$DB->insert_string('exp_extensions',
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'submit_relation',
					  'hook'         => 'submit_new_entry_end',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
		
		// -- Add modify_template
		$DB->query(
			$DB->insert_string('exp_extensions',
				array('extension_id' => '',
					  'class'        => get_class($this),
					  'method'       => 'modify_template',
					  'hook'         => 'weblog_entries_tagdata',
					  'settings'     => '',
					  'priority'     => 10,
					  'version'      => $this->version,
					  'enabled'      => 'y'
				)
			)
		);
	}
	
	//
	// Change Settings
	//
	function settings()
	{
		$settings = array();
		
		// Complex:
		// [variable_name] => array(type, values, default value)
		// variable_name => short name for setting and used as the key for language file variable
		// type:  t - textarea, r - radio buttons, s - select, ms - multiselect, f - function calls
		// values:  can be array (r, s, ms), string (t), function name (f)
		// default:  name of array member, string, nothing
		//
		// Simple:
		// [variable_name] => 'Butter'
		// Text input, with 'Butter' as the default.
		
		return $settings;
	}
	
	//
	// Update Extension (by FTP)
	//
	function update_extension($current = '')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		if ($current < '1.0.1' )
		{
			/*Update Query*/
		}
		
		$DB->query("UPDATE exp_extensions SET version = '".$DB->escape_str($this->version)."' WHERE class = '".get_class($this)."'");
	}
	
	//
	// Admin > Field Groups
	//
	function edit_field_groups( $out )
	{
		global $DB, $EXT, $LANG;
		
		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}
		
		if(preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/td>/is", $out, $matches))
		{
			foreach($matches[1] as $key=>$field_id)
			{
				$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id='".$field_id."' LIMIT 1");
				if($query->row["field_type"] == $this->type)
				{
					$replace = preg_replace("/(<td.*?<td.*?>).*?<\/td>/si", "$1".$this->name."</td>", $matches[0][$key]);
					$out = str_replace($matches[0][$key], $replace, $out);
				}
			}
			return $out;
		}
		else
		{
		    return $out;
		}
	}
	
	//
	//  Admin > Field Groups > Edit Custom Field
	//
	function edit_custom_field( $data, $r )
	{
		global $EXT, $LANG;
		
		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== false)
		{
			$r = $EXT->last_call;
		}
		
		// -- Add the <option />
		if($data["field_type"] == $this->type) { $selected = " selected=\"true\""; }else{ $selected = ""; }
		$r = preg_replace("/(<select.*?name=.field_type.*?value=.select.*?[\r\n])/is", "$1<option value=\"".$this->type."\"$selected>".$this->name."</option>\r", $r);
		
		// -- Add the JS
		$r = preg_replace("/(id\s*==\s*.rel.*?})/is", "$1\r\t\telse if (id == '".$this->type."')
\t\t{
\t\t\tdocument.getElementById('rel_block').style.display = \"none\";
\t\t\tdocument.getElementById('select_block').style.display = \"none\";
\t\t\tdocument.getElementById('pre_populate').style.display = \"none\";
\t\t\tdocument.getElementById('text_block').style.display = \"none\";
\t\t\tdocument.getElementById('textarea_block').style.display = \"block\";
\t\t\tdocument.getElementById('date_block').style.display = \"none\";
\t\t\tdocument.getElementById('rel_block').style.display = \"block\";	
\t\t\tdocument.getElementById('relationship_type').style.display = \"block\";	
\t\t\tdocument.getElementById('formatting_block').style.display = \"none\";
\t\t\tdocument.getElementById('formatting_unavailable').style.display = \"block\";
\t\t}", $r);
		
		// -- If existing field, select the proper blocks
		if(isset($data["field_type"]) && $data["field_type"] == $this->type)
		{
			preg_match("/(id=.relationship_type.*?display:\s*)none/", $r, $relationship_type);
			if(count($relationship_type) > 0)
			{
				$r = str_replace($relationship_type[0], $relationship_type[1] .= "block", $r);
			}
			preg_match("/(id=.rel_block.*?display:\s*)none/", $r, $rel_block);
			if(count($rel_block) > 0)
			{
				$r = str_replace($rel_block[0], $rel_block[1] .= "block", $r);
			}	
			preg_match("/(id=.textarea_block.*?display:\s*)none/", $r, $textarea_block);
			if(count($textarea_block) > 0)
			{
				$r = str_replace($textarea_block[0], $textarea_block[1] .= "block", $r);
			}
			preg_match("/(id=.formatting_block.*?display:\s*)block/", $r, $formatting_unavailable);
			if(count($formatting_unavailable) > 0)
			{
				$r = str_replace($formatting_unavailable[0], $formatting_unavailable[1] .= "none", $r);
			}
			preg_match("/(id=.formatting_unavailable.*?display:\s*)none/", $r, $formatting_unavailable);
			if(count($formatting_unavailable) > 0)
			{
				$r = str_replace($formatting_unavailable[0], $formatting_unavailable[1] .= "block", $r);
			}
		}
		
		return $r;
	}
	
	//
	//  Publish Tab
	//
	function publish( $row, $field_data )
	{
		global $DB, $DSP, $EXT, $LANG;
		$r = "";
		
		if($row["field_type"] == $this->type)
		{
			$r .= "<table border='0' cellpadding='0' cellspacing='0' style='margin-bottom:0;width:100%'><tr><td>";
			
			if ($row['field_related_to'] == 'blog')
			{
				$relto = 'exp_weblog_titles';
				$relid = 'weblog_id';
			}
			else
			{
				$relto = 'exp_gallery_entries';
				$relid = 'gallery_id';
			}
			
			if ($row['field_related_orderby'] == 'date')
				$row['field_related_orderby'] = 'entry_date';
				
			
			$sql = "SELECT entry_id, title FROM ".$relto." WHERE ".$relid." = '".$DB->escape_str($row['field_related_id'])."' ";
			$sql .= "ORDER BY ".$row['field_related_orderby']." ".$row['field_related_sort'];
			
			if ($row['field_related_max'] > 0)
			{
				$sql .= " LIMIT ".$row['field_related_max'];
			}
			
			$relquery = $DB->query($sql);
			
			if ($relquery->num_rows == 0)
			{
				$r .= $DSP->qdiv('highlight_alt', $LANG->line('no_related_entries'));
			}
			else
			{
				$relentry_id = array();
				if ( ! isset($_POST['field_id_'.$row['field_id']]) OR $which == 'save')
				{
					$relentries = array_filter(preg_split("/[\r\n]+/", $field_data));
					if(count($relentries) > 0)
					{
						$relentry = $DB->query("SELECT rel_child_id FROM exp_relationships WHERE rel_id IN (".implode(",", $relentries).")");
						
						if ($relentry->num_rows > 0)
						{
							foreach($relentry->result as $v) $relentry_id[] = $v['rel_child_id'];
						}
					}
				}
				else
				{
					$relentry_id = $_POST['field_id_'.$row['field_id']];
				}
				
				//Start edits by Doug Avery and Trevor Davis to use checkboxes instead of multi-select		
				$r .= '<style type="text/css">.multi_relate_labels label:hover { background: #d6dfea; cursor: pointer }</style>';
				$r .= '<ul  class="multi_relate_labels" style="overflow: hidden; width: 100%; list-style: none; padding: 0">';
				$i = 0;
				
				foreach ($relquery->result as $relrow)
				{
				  
				  $i++;
				  $r .= '<li style="width: 25%; float: left;">';
				  $r .= '<label style="display: block; padding: 3px" for="field_id_'.$row['field_id'].'_'.$i.'">';
				  $r .= $DSP->input_checkbox('field_id_'.$row['field_id'].'[]', $relrow['entry_id'], (in_array($relrow['entry_id'], $relentry_id)) ? 1 : 0, 'id="field_id_'.$row['field_id'].'_'.$i.'"');
				  $r .= $relrow['title']."</label>";
				  $r .= '</li>';
				  $r .= ( $i % 4 == 0 ) ? '<li style="clear: both; height: 0; overflow: hidden;"></li>' : '';

				}
				
				$r .= '</ul>';
				//End edits by Doug Avery and Trevor Davis
				
			}
        
			// Safari
			$r .= "</td></tr></table>";
		}
		else if($EXT->last_call !== false)
		{
			return $EXT->last_call;
		}
		
		return $r;
	}
	
	//
	//  Modify Post for EE Storage
	//
	function modify_post()
	{
		global $DB, $submitted_mrel;
		
		if(count($submitted_mrel) == 0) $submitted_mrel = array();
		
		$query = $DB->query("SELECT f.field_id FROM exp_weblog_fields AS f, exp_weblogs AS w WHERE w.weblog_id=".$_POST["weblog_id"]." AND f.group_id=w.field_group AND f.field_type='".$this->type."'");
		foreach($query->result as $field)
		{
			if(isset($_POST["field_id_".$field["field_id"]]))
			{
				if(is_array($_POST["field_id_".$field["field_id"]]))
				{
					$submitted_mrel["field_id_".$field["field_id"]] = $_POST["field_id_".$field["field_id"]];
					unset($_POST["field_id_".$field["field_id"]]);
				}
			}
			else
			{
				$submitted_mrel["field_id_".$field["field_id"]] = array();
			}
			
			foreach($_POST as $key=>$value)
			{
				if(preg_match("/field_id_".$field['field_id']."_\d*/", $key))
				{
					unset($_POST[$key]);
				}
			}
		}
	}
	
	//
	//  After we have an entry id, submit the relations
	//
	function submit_relation( $entry_id, $data )
	{
		global $DB, $FNS, $submitted_mrel;
		
		foreach($submitted_mrel as $field=>$rel_entries)
		{
			$field_type = $DB->query("SELECT field_related_to type FROM exp_weblog_fields WHERE field_id=".substr($field, 9));
			
			$existing_rel = $DB->query("SELECT $field field_data FROM exp_weblog_data WHERE entry_id=$entry_id LIMIT 1");
			$existing_data = array_filter(preg_split("/[\r\n]+/", $existing_rel->row['field_data']));
			
			foreach($existing_data as $data)
			{
				$remove_query = $DB->query("DELETE exp_relationships FROM exp_relationships WHERE rel_id=$data");
			}
			
			$inserted_rel = array();
			foreach($rel_entries as $rel_entry)
			{
				$reldata = array(
									'type'			=> $field_type->row['type'],
									'parent_id'		=> $entry_id,
									'child_id'		=> $rel_entry,
									'related_id'	=> $data['weblog_id']
								);
				
				$inserted_rel[] = $FNS->compile_relationship($reldata, TRUE);
			}
			
			$update_data = $DB->query("UPDATE exp_weblog_data SET $field='".implode("\r", $inserted_rel)."' WHERE entry_id=$entry_id");
		}
	}
	
	//
	//	Modify Template
	//
	function modify_template( $tagdata, $row, &$weblog )
	{
		global $DB, $FNS, $TMPL, $EXT, /*$all_fields,*/ $Weblog;
		$count = 0;
		
		if($EXT->last_call !== false)
		{
			$tagdata = $EXT->last_call;
		}
		
		$reference = isset($Weblog->query) ? "Weblog" : "weblog";
		
		foreach($TMPL->related_data as $key => $rel_data)
		{
			if(!isset($$reference->cfields[1])) continue;
			
			$field_id = $$reference->cfields[1][$rel_data['field_name']];
			
			preg_match('/'.LD.'related_entries:attributes(.*?)'.RD.'/', $rel_data['tagdata'], $attributes);
			$attributes = (isset($attributes[1]) && count($attributes[1]) > 0)?$FNS->assign_parameters($attributes[1]):array();
			$TMPL->related_data[$key]['tagdata'] = preg_replace('/'.LD.'related_entries:attributes(.*?)'.RD.'/', '', $TMPL->related_data[$key]['tagdata']);
			
			$inner_build = '';
			$field_data = array_filter(preg_split("/[\r\n]+/", $row['field_id_'.$field_id]));
			foreach($field_data as $count => $rel_entry)
			{
				if(isset($attributes['limit']) && $count >= $attributes['limit']) break;
				
				$$reference->rfields[$rel_data['field_name']] = $field_id;
				$$reference->related_entries[] = $rel_entry.'_'.$rel_data['marker'];
				$inner_build .= LD.'REL['.$rel_entry.']['.$rel_data['field_name'].']'.$rel_data['marker'].'REL'.RD;
			}
			
			$tagdata = str_replace(LD.'REL['.$rel_data['field_name'].']'.$rel_data['marker'].'REL'.RD, $inner_build, $tagdata);
		}
		
		return $tagdata;
	}

}

?>