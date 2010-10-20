<?php

/*
=====================================================
 This ExpressionEngine plugin was created by Aaron Fowler
 http://twitter.com/adfowler
=====================================================

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.

=====================================================
 File: pi.familee.php
-----------------------------------------------------
 Purpose: to output an unordered list of forward/reverse relationship links with no duplicates. 
=====================================================
*/

$plugin_info = array(
						'pi_name'			=> 'Familee',
						'pi_version'		=> '0.9',
						'pi_author'			=> 'Aaron Fowler',
						'pi_author_url'		=> 'http://twitter.com/adfowler',
						'pi_description'	=> 'Outputs an unordered list of forward/reverse relationship links with no duplicates.',
						'pi_usage'			=> familee::usage()
					);

class Familee {

	var $return_data='';
	
	function Familee()
	{
		global $TMPL, $DB;
		
		// Allowed orderby/sort parameters
		$allowable_orderby = array('entry_id', 'title', 'entry_date');
		$allowable_sort = array('asc', 'desc');
		
		// Fetch params
		$entry_id = $TMPL->fetch_param('entry_id');
		$path = $TMPL->fetch_param('path');
		$orderby = 'entry_date';
		$sort = 'DESC';
		
		// Parameter "entry_id" is required
		if ($entry_id === FALSE)
		{
			echo 'Parameter "entry_id" of exp:familee tag must be defined!<br><br>';
		}

		// The value of the parameter "entry_id" must be a number
		if (is_numeric($entry_id) === FALSE)
		{
			echo 'The value of the parameter "entry_id" of exp:familee tag must be a number!<br><br>';
		}
		
		
		if ($entry_id !== FALSE)
		{
			$weblog_sql = '';
			if ($TMPL->fetch_param('weblog_id'))
			{
				$weblog_ids = explode('|', $TMPL->fetch_param('weblog_id'));
				foreach ($weblog_ids as $wid)
				{
					if(is_numeric($wid) === FALSE)
					{
						echo 'All "weblog_id" parameters of exp:familee tag must be numbers!<br><br>';
					}
					else
					{
						if ($weblog_sql == '')
						{
							$weblog_sql = 'AND (weblog_id=' . $wid;
						}
						else
						{
							$weblog_sql .= ' OR weblog_id=' . $wid;
						}
					}
				}
				if ($weblog_sql != '')
				{
					$weblog_sql .= ')';
				}
			}
			
			foreach ($allowable_orderby as $param)
			{
				if (strtolower($TMPL->fetch_param('orderby')) == $param)
				{
					$orderby = strtolower($TMPL->fetch_param('orderby'));
				}
			}

			foreach ($allowable_sort as $param)
			{
				if (strtolower($TMPL->fetch_param('sort')) == $param)
				{
					$sort = strtolower($TMPL->fetch_param('sort'));
				}
			}
			
			// Create SQL query string for reverse relationships
			$sql = "SELECT entry_id, title, url_title, status, entry_date FROM exp_weblog_titles WHERE status != 'closed' " . $weblog_sql . " AND 
			(entry_id IN (SELECT rel_child_id FROM exp_relationships WHERE rel_parent_id=" . $entry_id . " AND rel_type='blog')
			OR entry_id IN (SELECT rel_parent_id from exp_relationships WHERE rel_child_id=" . $entry_id . " AND rel_type='blog' 
			AND rel_parent_id NOT IN (SELECT rel_child_id AS rel_id FROM exp_relationships WHERE rel_parent_id=" . $entry_id . " AND rel_type='blog')))
			ORDER BY " . $orderby . " " . $sort;
			// Perform SQL query
			$query = $DB->query($sql);
			foreach ($query->result as $row)
			{
				$this->return_data .= '<li><a href="' . $path . $row['url_title'] . '">' . $row['title'] . '</a></li>';
			}
			
			
			// Build the unordered list and return it
			if ($this->return_data !== '')
			{
				$class = $TMPL->fetch_param('class') ? ' class="' . $TMPL->fetch_param('class') . '"' : '';
				$id = $TMPL->fetch_param('id') ? ' id="' . $TMPL->fetch_param('id') . '"' : '';
				$html_start = $TMPL->fetch_param('html_start') . '<ul' . $class . $id . '>';
				$html_end = '</ul>' . $TMPL->fetch_param('html_end');
				$this->return_data = $html_start . $this->return_data . $html_end;
			}

		}

	}
	// END FUNCTION
  
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------
// This function describes how the plugin is used.
//  Make sure and use output buffering

function usage()
{
ob_start(); 
?>

PARAMETERS:

1) entry_id - This is the only required parameter. Allows you to specify entry id number.

2) weblog_id - Allows you to limit relationships to within one or more weblogs. Separate multiple weblogs with a pipe character.

3) orderby='entry_date' - Options are 'entry_date', 'title', or 'entry_id'

4) sort='DESC' - Options are 'ASC' or 'DESC'

5) path - Prepend a path to the returned url.

6) class - Add a class attribute to the <ul> tag.

7) id - Add an id attribute to the <ul> tag.

6) html_start - Add HTML before the opening <ul> tag.

7) html_end - Add HTML after the closing </ul> tag.

EXAMPLE OF USAGE:

{exp:familee entry_id="123" weblog_id="1|2|3" orderby="title" sort="asc" path="/{segment_2}/" class="nav-list" id="article-nav" html_start="<h4>Related Links</h4>" html_end="<p>That's all, folks!</p>"}


<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}
// END USAGE

}
// END CLASS

/* End of file pi.familee.php */ 