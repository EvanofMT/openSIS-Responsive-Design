<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  schools from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************

function ListOutputPrint_sch($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
//    print_r($result);
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('[^a-zA-Z0-9 _"]*', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('"', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('"', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('[^a-zA-Z0-9 _]+', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (strpos($val, $term) !== FALSE)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }


            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('<BR>', ' ', par_rep_cb('<!--.*-->', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('<[^>]+>', '', par_rep_cb("<div onclick='[^']+'>", '', par_rep_cb(' +', ' ', par_rep_cb('&[^;]+;', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('<SELECT.*</SELECT\>', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('<[^>]+>', '', par_rep_cb("<div onclick='[^']+'>", '', par_rep_cb(' +', ' ', par_rep_cb('&[^;]+;', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
        #echo '<CENTER>';
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 26;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=98% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='cell_medium' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1px solid #a9d5e9 \" style=\"border-collapse:collapse\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {

                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left class=even>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left class=even>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD class=even align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD class=even align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD class=even align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }
//
//                        $crs= array_count_values(array_column($result, 'COURSE_PERIOD_ID'));

            $val_cp = array();
            $val_cp_day = array();

            foreach ($result as $val_cp1) {
                if ($old_cp == $val_cp1['COURSE_PERIOD_ID'] || array_search($val_cp1['COURSE_PERIOD_ID'], $val_cp1)) {
                    $val_cp[$val_cp1['COURSE_PERIOD_ID']] = $val_cp[$val_cp1['COURSE_PERIOD_ID']] + 1;
                    if ($old_cp_day == $val_cp1['DAYS']) {
                        $val_cp_day[$val_cp1['COURSE_PERIOD_ID']][$val_cp1['DAYS']] = $val_cp_day[$val_cp1['COURSE_PERIOD_ID']][$val_cp1['DAYS']] + 1;
                    } else {
                        $val_cp_day[$val_cp1['COURSE_PERIOD_ID']][$val_cp1['DAYS']] = 1;
                    }
                } else {
                    $val_cp[$val_cp1['COURSE_PERIOD_ID']] = 1;
                    $val_cp_day[$val_cp1['COURSE_PERIOD_ID']][$val_cp1['DAYS']] = 1;
                }
                $old_cp_day = $val_cp1['DAYS'];
                $old_cp = $val_cp1['COURSE_PERIOD_ID'];
            }

            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>', '\\1', $value);
                        $value = par_rep_cb('<SELECT.*</SELECT\>', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("<div onclick='[^']+'>", '', $value));
                        else
                            $item[$key] = par_rep_cb("<div onclick='[^']+'>", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                        if ($_REQUEST['modname'] == 'grades/AdminProgressReports.php' || $_REQUEST['modname'] == 'grades/ProgressReports.php' || $_REQUEST['modname'] == 'users/TeacherPrograms.php?include=grades/ProgressReports.php')
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Progress Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        else if ($_REQUEST['modname'] == 'grades/ReportCards.php')
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Report Card</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        else
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Add / Drop Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {

                            //$crs_arr=$item['COURSE_PERIOD_ID'];

                            if (($key == 'COURSE' || $key == 'MARKING_PERIOD_ID') && $crs_arr[0] != $item['COURSE_PERIOD_ID']) {
                                echo "<TD $color rowspan=" . $val_cp[$item[COURSE_PERIOD_ID]] . ">";
                                if ($key == 'FULL_NAME')
                                    echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                                if ($color == Preferences('HIGHLIGHT'))
                                    echo '';
                                echo $item[$key];
                                if (!$item[$key])
                                    echo '&nbsp;';
                                if ($key == 'FULL_NAME')
                                    echo '<DIV>';
                                echo "</TD>";
                                //$kim++;
                            }


                            if ($key == 'DAYS' && (!in_array($item['DAYS'], $crs_arr_day) || $crs_arr[0] != $item['COURSE_PERIOD_ID'])) {
//                                                         echo $crs_arr_day[0].'=!'.$item['DAYS'];
//                                                    echo '<br>';
//                                                    echo $key; echo '<br>';
                                echo "<TD $color rowspan=" . $val_cp_day[$item[COURSE_PERIOD_ID]][$item['DAYS']] . ">";
                                if ($key == 'FULL_NAME')
                                    echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                                if ($color == Preferences('HIGHLIGHT'))
                                    echo '';
                                echo DaySnameMod($item[$key], 2);
                                if (!$item[$key])
                                    echo '&nbsp;';
                                if ($key == 'FULL_NAME')
                                    echo '<DIV>';
                                echo "</TD>";
                                //$kim++;
                            }

                            elseif ($key != 'COURSE' && $key != 'DAYS' && $key != 'MARKING_PERIOD_ID') {
                                echo "<TD $color>";
                                if ($key == 'FULL_NAME')
                                    echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                                if ($color == Preferences('HIGHLIGHT'))
                                    echo '';
                                echo $item[$key];
                                if (!$item[$key])
                                    echo '&nbsp;';
                                if ($key == 'FULL_NAME')
                                    echo '<DIV>';
                                echo "</TD>";
                            }
                        }
                    }
                }
                echo "</TR>";

                $crs_arr[0] = $item['COURSE_PERIOD_ID'];
                //echo $item['DAYS'];
                $crs_arr_day[0] = trim($item['DAYS']);
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left class=even>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left class=even>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table width=120px cellspacing=8 cellpadding=6 ><tr><TD align=left class=lone_add >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE cellpadding=6 cellspacing=1 width=96% class=\"grid\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR class=odd>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == 'class=even')
                            $color = $side_color;
                        else
                            $color = 'class=even';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD class=grid id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {

                    echo '<TD class=grid id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutput($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '', $custom_header = false,$headerVisibility = true) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }
    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);
    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    //$PHP_tmp_SELF = PreparePHP_SELF($tmp_REQUEST);
    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;

    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
//                    print_r($values);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        if ($key == 'ATTENDANCE' || $key == 'IGNORE_SCHEDULING')
                            $item[$key] = ($item[$key] == '<IMG SRC=assets/check.gif height=15>' ? 'Yes' : 'No');
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            if ($options['save_delimiter'] == 'xml') {
                foreach ($result as $item) {
                    foreach ($column_names as $key => $value) {
                        if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                            $item[$key] = str_replace(',', ';', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                        $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                    }
                    $output .= "\n";
                }
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {

            if (($result_count == 0 || $display_count == 0) && $plural) {
                echo '<div class="panel-body">';
                echo "<div class=\"alert alert-danger no-border m-b-0\">No $plural were found.</div>";
                echo '</div>';
            } elseif ($result_count == 0 || $display_count == 0) {
                echo '<div class="panel-body">';
                echo '<div class="alert alert-danger no-border">None were found.</div>';
                echo '</div>';
            }
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            if($headerVisibility==true){
            echo '<div class="panel-heading">';
            if ($custom_header != false) {
                echo $custom_header;
            } else {

                // SEARCH BOX & MORE HEADERS
                if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                    echo "<h6 class=\"panel-title\">";
                    if ($singular && $plural && $options['count']) {
                        if ($display_count > 1)
                            echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                        elseif ($display_count == 1)
                            echo "<span class=\"heading-text\">1 $singular was found.</span>";
                    }else {
                        echo '&nbsp;';
                    }
                    if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                        echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true class=\" btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                    echo '</h6>';
                    $colspan = 1;
                    if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                        $_REQUEST['portal_search'] = 'true';
                        $tmp_REQUEST = $_REQUEST;
                        unset($tmp_REQUEST['LO_search']);
                        unset($tmp_REQUEST['page']);
                        echo "<div class=\"heading-elements\">";
                        echo '<div class="form-group">';
                        echo "<INPUT type=hidden id=hidden_field >";
                        echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                        echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                        echo '</div>'; //.input-group
                        echo '</div>'; //.form-group
                        echo "</div>"; //.heading-elements
                        $colspan++;
                    }
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                } else {
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                }
            }
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            } // $headerVisibility
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition" class="clearfix"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }

            echo '<div class="table-responsive">';
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th " . (($i == 1) ? ' data-toggle="true"' : ' data-hide="phone"') . "><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR  class=\"$color\">";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD>" . button('remove', $button_title, $button_link) . "</TD>";
                }

                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD>";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                //if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';

                    $number_rec = 100;
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            echo '</TBODY>';
            echo "</TABLE>";
            echo '</div>'; //.table-responsive
            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<hr class="no-margin"/><table class="table"><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {


            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputPeriod($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '', $custom_header = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }
    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);
    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    //$PHP_tmp_SELF = PreparePHP_SELF($tmp_REQUEST);
    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;

    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(str_replace("'", "''", $val)) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
//                    print_r($values);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table border="1"><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        if ($key == 'ATTENDANCE' || $key == 'IGNORE_SCHEDULING') {
                            if ($key == 'ATTENDANCE')
                                $item[$key] = ($item['UA'] == 'Y' ? 'Yes' : 'No');
                            if ($key == 'IGNORE_SCHEDULING')
                                $item[$key] = ($item['IGS'] == 'Y' ? 'Yes' : 'No');
                        }
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            if ($options['save_delimiter'] == 'xml') {
                foreach ($result as $item) {
                    foreach ($column_names as $key => $value) {
                        if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                            $item[$key] = str_replace(',', ';', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                        $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                    }
                    $output .= "\n";
                }
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {

            if (($result_count == 0 || $display_count == 0) && $plural) {
                echo '<div class="panel-body">';
                echo "<div class=\"alert alert-danger no-border m-b-0\">No $plural were found.</div>";
                echo '</div>';
            } elseif ($result_count == 0 || $display_count == 0) {
                echo '<div class="panel-body">';
                echo '<div class="alert alert-danger no-border">None were found.</div>';
                echo '</div>';
            }
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            if ($custom_header != false) {
                echo $custom_header;
            } else {

                // SEARCH BOX & MORE HEADERS
                if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                    echo "<h6 class=\"panel-title\">";
                    if ($singular && $plural && $options['count']) {
                        if ($display_count > 1)
                            echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                        elseif ($display_count == 1)
                            echo "<span class=\"heading-text\">1 $singular was found.</span>";
                    }
                    if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                        echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true class=\" btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                    echo '</h6>';
                    $colspan = 1;
                    if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                        $_REQUEST['portal_search'] = 'true';
                        $tmp_REQUEST = $_REQUEST;
                        unset($tmp_REQUEST['LO_search']);
                        unset($tmp_REQUEST['page']);
                        echo "<div class=\"heading-elements\">";
                        echo '<div class="form-group">';
                        echo "<INPUT type=hidden id=hidden_field >";
                        echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                        echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                        echo '</div>'; //.input-group
                        echo '</div>'; //.form-group
                        echo "</div>"; //.heading-elements
                        $colspan++;
                    }
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                } else {
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                }
            }
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }

            echo '<div class="table-responsive">';
            echo "<TABLE id='results' class=\"table table-bordered\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th " . (($i == 1) ? ' data-toggle="true"' : ' data-hide="phone"') . "><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR  class=\"$color\">";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD>" . button('remove', $button_title, $button_link) . "</TD>";
                }

                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD>";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                echo '</div>';
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<hr class="no-margin"/><table class="table"><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {


            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputSchedule($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
//    pritn_r($result);

    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    //$PHP_tmp_SELF = PreparePHP_SELF($tmp_REQUEST);
    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {

        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {

                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {


                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags($val)) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {

                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {

            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                foreach ($column_names as $key => $value)
                    $output .= str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                $output .= "\n";
            }
            $output = '<table><tr><td><b>Course</b></td><td><b>Period - Teacher</b></td><td><b>Room</b></td><td><b>Term</b></td><td><b>Enrolled</b></td><td><b>Dropped</b></td></tr>';


            foreach ($result as $item) {

                $end = $item['END_DATE'];
                $output .= '<tr><td>' . strip_tags($item['TITLE']) . '</td><td>' . $item['PERIOD_PULLDOWN'] . '</td><td>' . $item['ROOM'] . '</td><td>' . $item['COURSE_MARKING_PERIOD_ID'] . '</td><td>' . $item['START_DATE'] . '</td><td>' . ($item['END_DATE'] == '' ? '' : $item['END_DATE']) . '</td></tr>';
            }
            $output .= "</table>";
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");

            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {

                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----

            echo '</div>'; //.panel-heading                        
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {

                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo ">$value</A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last

            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {


                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

//				
                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {

                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered table-striped\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";

                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {

                            echo "<TD>" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";

                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {


                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {

                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            $student_id = UserStudentID();
                            $qr = DBGet(DBQuery('select end_date from student_enrollment where student_id=' . $student_id . ' order by id desc limit 0,1'));

                            $stu_end_date = $qr[1]['END_DATE'];
                            $qr1 = DBGet(DBQuery('select end_date from course_periods where COURSE_PERIOD_ID=' . $item['COURSE_PERIOD_ID'] . ''));

                            $cr_end_date = $qr1[1]['END_DATE'];
                            if (strtotime($cr_end_date) > strtotime($stu_end_date) && $stu_end_date != '') {
                                echo '<FONT color=red>' . $item[$key] . '</FONT>';
                            } else {

                                echo $item[$key];
                            }
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }

                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {



                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {

                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";

                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD>" . button('add') . "</TD>";


                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {

                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {

            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {


            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {

                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputStaffCert($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);

    $PHP_tmp_SELF = PreparePHP_SELF($tmp_REQUEST);

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;


    // need to bring into css
    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }

        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (strpos($val, $term) !== FALSE)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---
        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {

                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

//            foreach ($result as $item) {
//                foreach ($column_names as $key => $value) {
//                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
//                        $item[$key] = str_replace(',', ';', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
//                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
//                }
//                $output .= "\n";
//            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---

        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }

        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {

            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }

            // END MISC ---

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                    if ($where_message)
                        echo $where_message;
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true" . " ><i class=\"icon-file-excel\"></i></a>";
//                    echo " &nbsp; <A HREF=" . encode_url(str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true") . " ><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
//            if (!isset($_REQUEST['_openSIS_PDF']))
//                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            //echo "<TABLE class=\"table table-bordered table-striped\" align=center>";
//            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                echo '<THEAD>';
//            if (!isset($_REQUEST['_openSIS_PDF']))
//                echo '<TR>';

            $i = 1;



            $color = '';

//            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                echo '</THEAD><TBODY>';
            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo button('add', $link['add']['title'], $link['add']['link']);
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo button('add') . $link['add']['span'];
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo $link['add']['html']['remove'];
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo button('add');

                    foreach ($column_names as $key => $value) {
                        echo $link['add']['html'][$key];
                    }
                    //echo "</TR>";
                    $count++;
                }
            }




            for ($j = $start; $j <= $stop; $j++) {    ////// MAIN CODE PORTION
                $item = $result[$j];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        //echo '</TABLE><TABLE class=\"table table-bordered table-striped\">';
                        echo '<div style="page-break-after: always;"></div><!-- NEW PAGE -->';
                    }
                    //echo "<TR>";
//                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
//                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo str_replace(' ', '&nbsp;', $value);
                        }
                    }
                    //echo "</TR>";
                }

                //echo '</TBODY>';
                //echo '</TABLE>';

                if ($count == 0)
                    $count = $br;

                //echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_type = $link['remove']['buttontype'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . urlencode($item[$val]);
                    }
                }

                if ($cols) {
                    $i = 0;
                    //echo "<TABLE cellpadding=6 cellspacing=1 width=96% class=hseparator><TR>";
                    echo '<div class="well m-b-20">';
                    echo '<div class="clearfix">';
                    echo '<div class="pull-right">' . button('remove', $button_title, $button_link, '', '', $button_type) . '</div>';
                    echo '</div>';
                    echo '<div class="row"><div class="col-md-6">';

                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            //echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            //echo "</TD>";
                        }

                        else {

                            if ($i < 3) {
                                //Here to change the ListOutput Header Colour
                                echo "<div class=\"form-group\"><label class=\"control-label col-md-4 text-right\">" . $value . "</label>";
                                echo "<div class=\"col-md-8\">" . $item[$key] . "</div></div>";
                                $i++;
                            } else {
                                if ($i == 3)
                                    echo '</div><div class="col-md-6">';
                                echo "<div class=\"form-group\"><label class=\"control-label col-md-4 text-right\">" . $value . "</label>";
                                echo "<div class=\"col-md-8\">" . $item[$key] . "</div></div>";
                                $i++;
                                if ($i > 5) {


                                    break;
                                }
                            }
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            //echo "</TD>";
                        }
                    }

                    //echo '</TABLE>';
                    echo '</div>'; //.col-md-6
                    echo '</div>'; //.row
                    $i = 0;
                    foreach ($column_names as $key => $value) {
                        if ($i == 6) {
                            echo '<div class="form-group">';
                            echo '<label class="col-md-2 control-label text-right">' . $value . '</label>';
                            echo '<div class="col-md-10">';
                            echo $item[$key];
                            echo '</div>';
                            echo '</div>';
                        }
                        $i++;
                    }

                    echo '</div>';
                }

                //echo "</TR>";
            }





            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {
                echo '<div class="well">';
                echo '<h4 class="text-primary m-t-0"><i class="icon-plus3 "></i> Add New Certification</h4>';

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo button('add', $link['add']['title'], $link['add']['link']);
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo button('add') . $link['add']['span'];

                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    //echo "<TR $color>";



                    $i = 0;
                    $j = 0;
                    //echo "<TABLE cellpadding=6 cellspacing=1 width=100% ><TR>";
//                    if ($link['add']['html']['remove'])
//                        echo '<TD>' . $link['add']['html']['remove'] . '</TD>';
//                    else
//                        echo '<TD>' . button('add') . '</TD>';

                    echo '<div class="row">';
                    echo '<div class="col-md-6">';
                    foreach ($column_names as $key => $value) {
                        if ($i == 1)
                            echo '</div><div class="col-md-6">';

                        //if ($i < 3) {
                        echo '<div class="form-group">';
                        //Here to change the ListOutput Header Colour
                        echo '<label class="control-label col-md-4 text-right">' . $value . '</label>';
                        echo '<div class="col-md-8">' . $link['add']['html'][$key] . '</div>';
                        echo '</div>';
                        $i++;
                        if ($i == 2) {
                            echo '</div></div><div class="row"><div class="col-md-6">';
                            $i = 0;
                        }
//                        } else {
//                            echo '<div class="form-group">';
//                            echo '<label class="control-label col-md-4 text-right">' . $value . '</label>';
//                            echo '<div class="col-md-8">' . $link['add']['html'][$key] . '</div>';
//                            echo '</div>';
                        $j++;
                        if ($j > 5)
                            break;
//                        }
                    }
                    echo '</div>';
                    echo '</div>';
                    $i = 0;
                    foreach ($column_names as $key => $value) {
                        if ($i == 6) {
                            echo '<div class="form-group">';
                            echo '<label class="control-label col-md-2 text-right">' . $value . '</label>';
                            echo '<div class="col-md-10">';
                            echo $link['add']['html'][$key];
                            echo '</div>';
                            echo '</div>';
                        }
                        $i++;
                    }
                    //echo "</TABLE></TD>";
                    //echo "</TR>";
                }
                echo '</div>';
            }
            if ($result_count != 0) {
//                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                    echo '</TBODY>';
//                echo "</TABLE>";
                // SHADOW
//                if (!isset($_REQUEST['_openSIS_PDF']))
//                    echo '</TD ></TR></TABLE>';
//                echo "</TD ></TR>";
//                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }

        if ($result_count == 0) {

            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF'])) {
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                } elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {

                    $color = $side_color;

                    echo '<div class="well">';
                    // WIDTH=100%
                    // SHADOW
                    if ($link['add']['html']) {

                        /* Here also change the colour for left corner */

                        if ($link['add']['html']['remove']) {
                            echo '<div><div class="pull-right">' . $link['add']['html']['remove'] . '</div><h4></h4></div>';
                        } else {
                            echo '<div><h4 class="text-primary m-t-0">' . button('add') . ' Add New Certification</h4></div>';
                        }


                        $i = 0;
                        echo '<div class="row">';

                        echo '<div class="col-md-6">';
                        foreach ($column_names as $key => $value) {
                            if ($i < 3) {
                                //Here to change the ListOutput Header Colour
                                echo '<div class="form-group"><label class="control-label text-right col-lg-4">' . $value . '</label>';
                                echo '<div class="col-lg-8">' . $link['add']['html'][$key] . '</div>';
                                echo '</div>';
                                $i++;
                            } else {
                                if ($i == 3) {
                                    echo '</div><div class="col-md-6">';
                                }
                                echo '<div class="form-group"><label class="control-label text-right col-lg-4">' . $value . "</label>";
                                echo '<div class="col-lg-8">' . $link['add']['html'][$key] . '</div>';
                                echo '</div>';
                                $i++;
                                if ($i > 5)
                                    break;
                            }
                        }
                        echo '</div>';
                        echo "</div>";

                        $i = 0;
                        foreach ($column_names as $key => $value) {
                            if ($i == 6) {

                                echo '<div class="form-group"><label class="control-label text-right col-lg-2">' . $value . '</label>';
                                echo '<div class="col-lg-10">';
                                echo $link['add']['html'][$key] . "</label>";
                                echo '</div>';
                                echo '</div>';
                            }
                            $i++;
                        }
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW
                    echo '</div>';
                }
        }

        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputMod($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;


    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (par_rep($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {
                echo "<TABLE border=0";
                //if(isset($_REQUEST['_openSIS_PDF']))
                echo " width=100%";
                echo " ><TR><TD>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {

                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);

                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX 
            // SHADOW
            echo "<TABLE class='table table-bordered table-striped'>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=\"text-grey-800\">$value</A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD $color>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD $color>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD $color >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered table-striped\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD>" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '';
                            echo $item[$key];
                            echo '';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD $color>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD $color>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD $color >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                echo '</TD></TR></TABLE>';
            if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                echo '<div class="panel-footer">' . button('add', $link['add']['title'], $link['add']['link']) . '</div>';
            elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                $color = $side_color;


                if ($link['add']['html']) {
                    /* Here also change the colour for left corner */
                    echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                    foreach ($column_names as $key => $value) {
                        //Here to change the ListOutput Header Colour
                        echo "<TD><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                    }
                    echo "</TR>";

                    echo "<TR>";

                    if ($link['add']['html']['remove'])
                        echo "<TD>" . $link['add']['html']['remove'] . "</TD>";
                    else
                        echo "<TD >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    echo "</TABLE>";
                } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TABLE ><TR><TD align=left>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                // SHADOW
            }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputPrint_Report($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);

    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {

                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
        #echo '<CENTER>';
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 26;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1 \" style=\"border-collapse:collapse\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                        echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">" . $_SESSION['_REQUEST_vars'][0] . "</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';

                            if (count(explode(',', $item[$key])) > 1) {
                                $room = explode(',', $item[$key]);
                                for ($v = 0; $v < count(explode(',', $item[$key])); $v++) {
                                    echo $room[$v] . '';
                                }
                            } else
                                echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {

                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputPrint($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
        #echo '<CENTER>';
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 26;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1px solid #a9d5e9 \" style=\"border-collapse:collapse\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {

                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        //$value = eregi_replace('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        //$value = eregi_replace('<SELECT.*</SELECT\>', '', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                        if ($_REQUEST['modname'] == 'grades/AdminProgressReports.php' || $_REQUEST['modname'] == 'grades/ProgressReports.php' || $_REQUEST['modname'] == 'users/TeacherPrograms.php?include=grades/ProgressReports.php')
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Progress Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        else if ($_REQUEST['modname'] == 'grades/ReportCards.php')
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Report Card</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        else
                            echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Add / Drop Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {

                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputCustom($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                //$output .= '<td>' . str_replace('&nbsp;', ' ', eregi_replace('<BR>', ' ', ereg_replace('<!--.*-->', '', $value))) . '</td>';
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        //$output .='<td>' . ereg_replace('<[^>]+>', '', ereg_replace("<div onclick='[^']+'>", '', ereg_replace(' +', ' ', ereg_replace('&[^;]+;', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    //$item[$key] = eregi_replace('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    //$item[$key] = eregi_replace('<SELECT.*</SELECT\>', '', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 22;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1px solid #a9d5e9 \" style=\"border-collapse:collapse\" align=center>";


            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        //$value = eregi_replace('<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        //$value = eregi_replace('<SELECT.*</SELECT\>', '', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                        //$item[$key] = str_replace(' ', '&nbsp;', ereg_replace("<div onclick='[^']+'>", '', $value));
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                        //$item[$key] = ereg_replace("<div onclick='[^']+'>", '', $value);
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";

                        echo '</table>';
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD  id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

############# Print Catalog function ##############################

function PrintCatalog($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));


    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 26;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1px solid #a9d5e9 \" style=\"border-collapse:collapse\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                        echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">Student Advanced Report</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";

                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {

                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

####################Print Catalog Function ENds Here ###########################################################
#### ------------------------------- List Output For Missing Attn. ---------------------------------------------- ###

function ListOutput_missing_attn($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {

    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

//    if (!isset($options['add'])) {
//        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
//            if ($link) {
//                unset($link['add']);
//                unset($link['remove']);
//            }
//        }
//    }
    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 10000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);

    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));


    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            $output = '';
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            if ($options['save_delimiter'] == 'xml') {
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

      if ($options['count'] || $display_zero) {

            if (($result_count == 0 || $display_count == 0) && $plural) {
                echo '<div class="panel-body">';
                echo "<div class=\"alert alert-danger no-border m-b-0\">No $plural were found.</div>";
                echo '</div>';
            } elseif ($result_count == 0 || $display_count == 0) {
                echo '<div class="panel-body">';
                echo '<div class="alert alert-danger no-border">None were found.</div>';
                echo '</div>';
        }
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                        }
                    }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

//            echo '<div class="panel-heading">';
//            if ($custom_header != false) {
//                echo $custom_header;
//            } else {
//
//            // SEARCH BOX & MORE HEADERS
//            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
//                    echo "<h6 class=\"panel-title\">";
//                if ($singular && $plural && $options['count']) {
//                    if ($display_count > 1)
//                            echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
//                    elseif ($display_count == 1)
//                            echo "<span class=\"heading-text\">1 $singular was found.</span>";
//                }
//                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
//                        echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true class=\" btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";
//
//                echo '</h6>';
//                $colspan = 1;
//                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
//                        $_REQUEST['portal_search'] = 'true';
//                    $tmp_REQUEST = $_REQUEST;
//                    unset($tmp_REQUEST['LO_search']);
//                    unset($tmp_REQUEST['page']);
//                        echo "<div class=\"heading-elements\">";
//                    echo '<div class="form-group">';
//                        echo "<INPUT type=hidden id=hidden_field >";
//                        echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
//                        echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
//                        echo '</div>'; //.input-group
//                    echo '</div>'; //.form-group
//                        echo "</div>"; //.heading-elements
//                    $colspan++;
//                }
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//                } else {
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//                }
//            }
//            // END SEARCH BOX ----
//            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition" class="clearfix"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }

            echo '<div class="table-responsive">';
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
                echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th " . (($i == 1) ? ' data-toggle="true"' : ' data-hide="phone"') . "><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR  class=\"$color\">";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                       
                        }

                    echo "<TD>" . button_missing_atn('remove', ''.$button_title, $button_link) . "</TD>";
                    }

                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD>";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            

            echo '</TBODY>';
            echo "</TABLE>";
            echo '</div>'; //.table-responsive
            // END PRINT THE LIST ---
            
            
            if ($result_count != 0) {
                //if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';

                    $number_rec = 50;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo "$('#results').DataTable({"
                        . "dom: '<\"datatable-header\"ip><\"datatable-scroll\"t><\"datatable-footer\"ip>',"
                        . "language: {
                            search: '<span>Filter:</span> _INPUT_',
                            lengthMenu: '<span>Show:</span> _MENU_',
                            paginate: { 'first': 'First', 'last': 'Last', 'next': '&rarr;', 'previous': '&larr;' }
                        },
                        drawCallback: function () {
                            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
                        },
                        preDrawCallback: function() {
                            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').removeClass('dropup');
                        },
                        'iDisplayLength': ".$number_rec.""
                        . "});";
                        echo "$('.dataTables_length select').select2({
                            minimumResultsForSearch: Infinity,
                            width: 'auto'
                        });";
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<hr class="no-margin"/><table class="table"><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

#### ------------------------------- List Output For Missing Attn. End ---------------------------------------- ###
#### ------------------------------- List Output For Missing Attn. in Teacher portal -------------------------- ###

function ListOutput_missing_attn_teach_port($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {

    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

//    if (!isset($options['add'])) {
//        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
//            if ($link) {
//                unset($link['add']);
//                unset($link['remove']);
//            }
//        }
//    }
    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 10000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);

    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));


    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            $output = '';
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            if ($options['save_delimiter'] == 'xml') {
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

//        if ($options['count'] || $display_zero) {
//
//            if (($result_count == 0 || $display_count == 0) && $plural) {
//                echo '<div class="panel-body">';
//                echo "<div class=\"alert alert-danger no-border m-b-0\">No $plural were found.</div>";
//                echo '</div>';
//            } elseif ($result_count == 0 || $display_count == 0) {
//                echo '<div class="panel-body">';
//                echo '<div class="alert alert-danger no-border">None were found.</div>';
//                echo '</div>';
//            }
//        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                        }
                    }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

//            echo '<div class="panel-heading">';
//            if ($custom_header != false) {
//                echo $custom_header;
//            } else {
//
//            // SEARCH BOX & MORE HEADERS
//            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
//                    echo "<h6 class=\"panel-title\">";
//                if ($singular && $plural && $options['count']) {
//                    if ($display_count > 1)
//                            echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
//                    elseif ($display_count == 1)
//                            echo "<span class=\"heading-text\">1 $singular was found.</span>";
//                }
//                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
//                        echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true class=\" btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";
//
//                echo '</h6>';
//                $colspan = 1;
//                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
//                        $_REQUEST['portal_search'] = 'true';
//                    $tmp_REQUEST = $_REQUEST;
//                    unset($tmp_REQUEST['LO_search']);
//                    unset($tmp_REQUEST['page']);
//                        echo "<div class=\"heading-elements\">";
//                    echo '<div class="form-group">';
//                        echo "<INPUT type=hidden id=hidden_field >";
//                        echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
//                        echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
//                        echo '</div>'; //.input-group
//                    echo '</div>'; //.form-group
//                        echo "</div>"; //.heading-elements
//                    $colspan++;
//                }
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//                } else {
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//                }
//            }
//            // END SEARCH BOX ----
//            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition" class="clearfix"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }

            echo '<div class="table-responsive">';
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
                echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th " . (($i == 1) ? ' data-toggle="true"' : ' data-hide="phone"') . "><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR  class=\"$color\">";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                        if ($_SESSION['take_mssn_attn'] && $var == 'cp_id') {
                            $cur_cp_id = $item[$val];
                        }
                        }

                    echo "<TD>" . button_missing_atn('remove', $button_title, $button_link) . "</TD>";
                    }

                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD>";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                //if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                // SHADOW
                

                if ($options['center'])
                    echo '';
            }

            echo '</TBODY>';
            echo "</TABLE>";
            echo '</div>'; //.table-responsive
            
            
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';

                    $number_rec = 50;                    
                    if ($result_count > $number_rec) {
                        echo "<script type='text/javascript'>\n";
                        echo "$('#results').DataTable({"
                        . "dom: '<\"datatable-header\"ip><\"datatable-scroll\"t><\"datatable-footer\"ip>',"
                        . "language: {
                            search: '<span>Filter:</span> _INPUT_',
                            lengthMenu: '<span>Show:</span> _MENU_',
                            paginate: { 'first': 'First', 'last': 'Last', 'next': '&rarr;', 'previous': '&larr;' }
                        },
                        drawCallback: function () {
                            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').addClass('dropup');
                        },
                        preDrawCallback: function() {
                            $(this).find('tbody tr').slice(-3).find('.dropdown, .btn-group').removeClass('dropup');
                        },
                        'iDisplayLength': ".$number_rec.""
                        . "});";
                        echo "$('.dataTables_length select').select2({
                            minimumResultsForSearch: Infinity,
                            width: 'auto'
                        });";
                        echo "</script>\n";
                    }
                }
            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<hr class="no-margin"/><table class="table"><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

#### ------------------------ List Output For Missing Attn. in Teacher portal End ------------------------------ ###

function ListOutputGrade_old($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {

    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {

                $r = array();
                $a = array();
                $t = array();
                $c = 0;
                for ($i = 1; $i <= count($result); $i++) {
                    if (array_key_exists("FULL_NAME", $result[$i])) {
                        array_push($a, $i);
                    }
                }

                $l = 0;
                $k = 0;
                foreach ($result as $column => $value) {

                    for ($n = 0; $n < count($a); $n++) {
                        if ($column == $a[$n]) {
                            $k = $k + 1;
                        }
                    }

                    $t[$k][$l] = $value;
                    $l++;
                }


                for ($h = 1; $h <= count($a); $h++) {

                    foreach ($t[$h] as $sort) {
                        if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                            $sort_array[] = $sort[$_REQUEST['LO_sort']];
                        else
                            $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                    }


                    if ($_REQUEST['LO_direction'] == -1)
                        $dir = SORT_DESC;
                    else
                        $dir = SORT_ASC;

                    if (count($t) > 1) {


                        if (is_int($sort_array[1]) || is_double($sort_array[1]))
                            array_multisort($sort_array, $dir, SORT_NUMERIC, $t[$h]);
                        else
                            array_multisort($sort_array, $dir, $t[$h]);



                        $inc = 0;

                        $pos = 0;
                        $flag = true;
                        $inc = 0;


                        $select = $_REQUEST['LO_sort'];
                        for ($c = 0; $c < count($t[$h]); $c++) {
                            if (array_key_exists($_REQUEST['LO_sort'], $t[$h][$c])) {
                                $temp = $t[$h][$c];

                                if ($temp[$select]) {
                                    $inc++;
                                    if ($flag) {
                                        $pos = $c;
                                        $flag = false;
                                    }
                                }
                            }
                        }

                        $abc = array_slice($t[$h], $pos, $inc);

                        if ($pos == 0)
                            $cde = array_slice($t[$h], $inc, (count($t[$h]) - 1));
                        else
                            $cde = array_slice($t[$h], 0, $pos);


                        if ($inc != 0) {
                            $t[$h] = array_merge($abc, $cde);
                        }


                        echo "<br/>";
                        array_push($result, $t[$h]);
                    }

                    for ($i = $result_count - 1; $i >= 0; $i--) {
                        $result[$i + 1] = $result[$i];
                    }

                    unset($result[0]);

                    $sort_array = "";
                }


                $bgcolor_sort = array();
                for ($h = 1; $h <= count($t); $h++) {

                    for ($n = 0; $n < count($t[$h]); $n++) {

                        if ($_REQUEST['LO_sort'] == "FULL_NAME") {

                            if (array_key_exists("FULL_NAME", $t[$h][$n])) {

                                $name_sort[] = array_shift($t[$h][$n]);
                            }
                            if (array_key_exists("bgcolor", $t[$h][$n])) {
                                $bgcolor_sort[] = array_shift($t[$h][$n]);
                            }
                        } else {


                            if (array_key_exists("FULL_NAME", $t[$h][$n])) {
                                $FULL_NAME = array_shift($t[$h][$n]);
                            }
                            if (array_key_exists("bgcolor", $t[$h][$n])) {
                                $bgcolor = array_shift($t[$h][$n]);
                            }
                            $t[$h][0][FULL_NAME] = $FULL_NAME;
                            $t[$h][0][bgcolor] = $bgcolor;
                        }
                    }
                }
                for ($h = 1; $h <= count($t); $h++) {

                    for ($n = 0; $n < count($t[$h]); $n++) {

                        if (array_key_exists("0", $t[$h][$n])) {

                            $mkperiod = $t[$h][$n]['MARKING_PERIOD_ID'];
                            $t[$h][$n][$mkperiod] = $t[$h][$n][0];
                        }
                    }
                }

                if ($_REQUEST['LO_sort'] == "FULL_NAME") {
                    array_multisort($name_sort, $dir);

                    for ($h = 1; $h <= count($t); $h++) {
                        $t[$h][0][FULL_NAME] = $name_sort[$h - 1];
                    }
                }
                $result = "";
                for ($n = 1; $n <= count($a); $n++) {

                    $result = array_merge((array) $result, $t[$n]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            foreach ($result as $item) {

                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';
                echo '<TABLE class="table table-bordered table-striped">';
                echo "<TR><TD align=left valign=middle>";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<h6 class=\"panel-title\"><span class=\"heading-text\">$display_count $plural were found.</span></h6>";
                    elseif ($display_count == 1)
                        echo "<h6 class=\"panel-title\"><span class=\"heading-text\">1 $singular was found.</span></h6>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo "<A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true ><i class=\"icon-file-excel\"></i></a>";

                echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle style="white-space:nowrap;">&nbsp;&nbsp;';
                    echo "<INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {

                    foreach ($column_names as $key => $value) {

                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW				
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputGrade($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {

    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            foreach ($result as $item) {

                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            //echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                //echo '<TD align=center>';
                //echo '<TABLE class="table table-bordered table-striped">';
                echo '<div class="panel-heading">';
                echo '<h6 class="panel-title">';
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A class=\"btn btn-success btn-xs btn-icon text-white\" HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true ><i class=\"icon-file-excel\"></i></a>";
                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<div class="heading-elements">';
                    echo '<div class="form-group">';
                    echo '<div class="input-group">';
                    echo "<INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''). "' placeholder=\"Search\" onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '><span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo '</div>'; //.heading-elements
                    $colspan++;
                }
                echo '</div>'; //.panel-heading
                //echo "</TR>";
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            //echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                echo '<div class="table-responsive">';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TH class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TH>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TH class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TH>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {

                    foreach ($column_names as $key => $value) {

                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    echo '</div>'; //.table-responsive


                    $number_rec = 100;                   
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }
//                echo "</TD ></TR>";
//                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW				
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputPrint_Institute_Report($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));

    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = 'bgcolor="#f5f5f5"';

    if ($group_count && $result_count) {
        $color = 'style=" background-color:#fff; padding:3px 4px 3px 4px;"';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == 'bgcolor="#f5f5f5"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#f5f5f5"';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == 'bgcolor="#ffffff"')
                            $color = $side_color;
                        else
                            $color = 'bgcolor="#ffffff"';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == 'bgcolor="#ffffff"')
                                    $color = $side_color;
                                else
                                    $color = 'bgcolor="#ffffff"';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }
            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>No $plural were found.</b></td></tr><tr><td colspan=2 ></td></tr></table></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div style=text-align:left><table cellpadding=1 cellspacing=0 ><tr><td ></td><td ><b>None were found.</b></td></tr><tr><td colspan=2></td></tr></table></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$_REQUEST['page'])
                    $_REQUEST['page'] = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($_REQUEST['page'] - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {
                    $where_message = "<SMALL>Displaying $start through $stop</SMALL>";
                    echo "Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . "<BR>";
                    }
                    else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $_REQUEST['page'])
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($_REQUEST['page'] + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;
                    echo '</TD></TR></TABLE>';
                    echo '<BR>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 100;
                if ($options['print']) {
                    $html = explode('', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%
            echo '<TABLE width=100% border=0 cellspacing=0 cellpadding=0><TR>';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo '<TD align=center>';

                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo '<TD height="50" align=right valign=middle>';
                    echo "<INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+this.value; return false;} '>&nbsp;&nbsp;<INPUT type=button class='btn_go' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"LO_search\").value;'></TD>";
                    $colspan++;
                }
                echo "</TR>";
                echo '<TR style="height:0;"><TD width=100% align=center colspan=' . $colspan . '><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV></TD></TR></TABLE>';
            } else
                echo '<TD width=100% align=right><DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</TD></TR><TR><TD>';

            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            echo "<TABLE cellpadding=6 width=100% cellspacing=1 border=\"1 \" style=\"border-collapse:collapse\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort'])
                        echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = 'bgcolor="#ffffff"';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == 'bgcolor="#ffffff"')
                    $color = $side_color;
                else
                    $color = 'bgcolor="#ffffff"';





                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE>';
                        echo "<div style=\"page-break-after: always;\"></div>";
                        echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
                        echo "<tr><td width=105>" . DrawLogo() . "</td><td style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetSchool(UserSchool()) . "<div style=\"font-size:12px;\">" . $_SESSION['_REQUEST_vars'][0] . "</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
                        echo '<TABLE cellpadding=6 width=100% cellspacing=1 border="1px solid #a9d5e9 " style="border-collapse:collapse" align=center>';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#d3d3d3></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD bgcolor=#d3d3d3 >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }






                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];
                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';

                            if (count(explode(',', $item[$key])) > 1) {
                                $room = explode(',', $item[$key]);
                                for ($v = 0; $v < count(explode(',', $item[$key])); $v++) {
                                    echo $room[$v] . '';
                                }
                            } else
                                echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = 'bgcolor=#ffffff';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD bgcolor=#ffffff align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD bgcolor=#ffffff align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD bgcolor=#ffffff align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                if (!isset($_REQUEST['_openSIS_PDF']))
                    echo '</TD ></TR></TABLE>';
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }
        }
        if ($result_count == 0) {
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            echo "<TD class=subtabs><A><b>" . str_replace(' ', '&nbsp;', $value) . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<div style="page-break-before: inherit;">&nbsp;</div>';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {

                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputStaffPrint($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            foreach ($column_names as $ci => $cd) {
                $column_names_mod[$ci] = strip_tags($cd);
            }
            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            foreach ($result as $item) {
                foreach ($column_names_mod as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---


            echo '<div class="panel-heading">';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo "&nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true ><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                   
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

//function ListOutputExcel($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
//    if (!isset($options['save']))
//        $options['save'] = true;
//    if (!isset($options['print']))
//        $options['print'] = true;
//    if (!isset($options['search']))
//        $options['search'] = true;
//    if (!isset($options['center']))
//        $options['center'] = true;
//    if (!isset($options['count']))
//        $options['count'] = true;
//    if (!isset($options['sort']))
//        $options['sort'] = true;
//    if (!$link)
//        $link = array();
//
//    if (isset($_REQUEST['page'])) {
//        if ($_REQUEST['list_type'] == $singular) {
//            $Request_page = $_REQUEST['page'];
//        }
//    }
//
//    if (!isset($options['add'])) {
//        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
//            if ($link) {
//                unset($link['add']);
//                unset($link['remove']);
//            }
//        }
//    }
//
//    // PREPARE LINKS ---
//    $result_count = $display_count = count($result);
//    $num_displayed = 100000;
//    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);
//
//    $tmp_REQUEST = $_REQUEST;
//    unset($tmp_REQUEST['page']);
//    unset($tmp_REQUEST['LO_sort']);
//    unset($tmp_REQUEST['LO_direction']);
//    unset($tmp_REQUEST['LO_search']);
//    unset($tmp_REQUEST['remove_prompt']);
//    unset($tmp_REQUEST['remove_name']);
//    unset($tmp_REQUEST['LO_save']);
//    unset($tmp_REQUEST['PHPSESSID']);
//
//
//
//    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
//    // END PREPARE LINKS ---
//    // UN-GROUPING
//    $group_count = count($group);
//    if (!is_array($group))
//        $group_count = false;
//
//
//
//    $side_color = '';
//
//    if ($group_count && $result_count) {
//        $color = '';
//        $group_result = $result;
//        unset($result);
//        $result[0] = '';
//
//        foreach ($group_result as $item1) {
//            if ($group_count == 1) {
//                if ($color == '')
//                    $color = $side_color;
//                else
//                    $color = '';
//            }
//
//            foreach ($item1 as $item2) {
//                if ($group_count == 1) {
//                    $i++;
//                    if (count($group[0]) && $i != 1) {
//                        foreach ($group[0] as $column)
//                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
//                    }
//                    $item2['row_color'] = $color;
//                    $result[] = $item2;
//                } else {
//                    if ($group_count == 2) {
//                        if ($color == '')
//                            $color = $side_color;
//                        else
//                            $color = '';
//                    }
//
//                    foreach ($item2 as $item3) {
//                        if ($group_count == 2) {
//                            $i++;
//                            if (count($group[0]) && $i != 1) {
//                                foreach ($group[0] as $column)
//                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
//                            }
//                            if (count($group[1]) && $i != 1) {
//                                foreach ($group[1] as $column)
//                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
//                            }
//                            $item3['row_color'] = $color;
//                            $result[] = $item3;
//                        } else {
//                            if ($group_count == 3) {
//                                if ($color == '')
//                                    $color = $side_color;
//                                else
//                                    $color = '';
//                            }
//
//                            foreach ($item3 as $item4) {
//                                if ($group_count == 3) {
//                                    $i++;
//                                    if (count($group[2]) && $i != 1) {
//                                        foreach ($group[2] as $column)
//                                            unset($item4[$column]);
//                                    }
//                                    $item4['row_color'] = $color;
//                                    $result[] = $item4;
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//            $i = 0;
//        }
//        unset($result[0]);
//        $result_count = count($result);
//
//        unset($_REQUEST['LO_sort']);
//    }
//    // END UN-GROUPING
//    $_LIST['output'] = true;
//
//
//    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
//    if ($_LIST['output'] != false) {
//        if ($result_count != 0) {
//            $count = 0;
//            $remove = count($link['remove']['variables']);
//            $cols = count($column_names);
//
//            // HANDLE SEARCHES ---
//            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
//                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
//                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));
//
//                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
//                    $search_term = par_rep_cb('/"/', '', $search_term);
//                    while ($space_pos = strpos($search_term, ' ')) {
//                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
//                        $search_term = substr($search_term, ($space_pos + 1));
//                    }
//                    $terms[trim($search_term)] = 1;
//                } else {
//                    $search_term = par_rep_cb('/"/', '', $search_term);
//                    $terms[trim($search_term)] = 1;
//                }
//
//                unset($terms['of']);
//                unset($terms['the']);
//                unset($terms['a']);
//                unset($terms['an']);
//                unset($terms['in']);
//
//                foreach ($result as $key => $value) {
//                    $values[$key] = 0;
//                    foreach ($value as $name => $val) {
//                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
//                        if (strtolower($_REQUEST['LO_search']) == $val)
//                            $values[$key] += 25;
//                        foreach ($terms as $term => $one) {
//                            if (ereg($term, $val))
//                                $values[$key] += 3;
//                        }
//                    }
//                    if ($values[$key] == 0) {
//                        unset($values[$key]);
//                        unset($result[$key]);
//                        $result_count--;
//                        $display_count--;
//                    }
//                }
//                if ($result_count) {
//                    print_r($result_count);
//                    array_multisort($values, SORT_DESC, $result);
//                    $result = ReindexResults($result);
//                    $values = ReindexResults($values);
//
//                    $last_value = 1;
//                    $scale = (100 / $values[$last_value]);
//
//                    for ($i = $last_value; $i <= $result_count; $i++)
//                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
//                }
//                $column_names['RELEVANCE'] = "Relevance";
//
//                if (is_array($group) && count($group)) {
//                    $options['count'] == false;
//                    $display_zero = true;
//                }
//            }
//
//            // END SEARCHES ---
//
//            if ($_REQUEST['LO_sort']) {
//                foreach ($result as $sort) {
//                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
//                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
//                    else
//                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
//                }
//                if ($_REQUEST['LO_direction'] == -1)
//                    $dir = SORT_DESC;
//                else
//                    $dir = SORT_ASC;
//
//                if ($result_count > 1) {
//                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
//                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
//                    elseif (VerifyDate_sort($sort_array[1]))
//                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
//                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
//                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
//                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
//                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
//                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
//                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
//                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
//                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
//                    else
//                        array_multisort($sort_array, $dir, $result);
//                    for ($i = $result_count - 1; $i >= 0; $i--)
//                        $result[$i + 1] = $result[$i];
//                    unset($result[0]);
//                }
//            }
//        }
//        // HANDLE SAVING THE LIST ---
//
//        if ($_REQUEST['LO_save'] == '1') {
//            $output = '';
//            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
//                $options['save_delimiter'] = 'comma';
//            switch ($options['save_delimiter']) {
//                case 'comma':
//                    $extension = 'csv';
//                    break;
//                case 'xml':
//                    $extension = 'xml';
//                    break;
//                default:
//                    $extension = 'xls';
//                    break;
//            }
//            ob_end_clean();
//
//            foreach ($column_names as $ci => $cd) {
//                $column_names_mod[$ci] = strip_tags($cd);
//            }
////			if($options['save_delimiter']!='xml')
////			{
////				foreach($column_names_mod as $key=>$value)
////					$output .= str_replace('&nbsp;',' ',eregi_replace('<BR>',' ',ereg_replace('<!--.*-->','',$value))) . ($options['save_delimiter']=='comma'?',':"\t");
////				$output .= "\n";
////			}
//
//            if ($options['save_delimiter'] != 'xml') {
//
//                $output = '<table><tr>';
//                foreach ($column_names_mod as $key => $value)
//                    if ($key != 'CHECKBOX')
//                        $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
//                $output .='</tr>';
//                foreach ($result as $item) {
//                    $output .='<tr>';
//                    foreach ($column_names_mod as $key => $value) {
//                        if ($key != 'CHECKBOX')
//                            $output .='<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
//                    }
//                    $output .='</tr>';
//                }
//                $output .='</table>';
//            }
//
//            foreach ($result as $item) {
//                foreach ($column_names_mod as $key => $value) {
//                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
//                        $item[$key] = str_replace(',', ';', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
//                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
//                }
//                $output .= "\n";
//            }
//
//            header("Cache-Control: public");
//            header("Pragma: ");
//            header("Content-Type: text/plain");
//            header("Content-Type: application/$extension");
//            $file_name = ProgramTitle() . '.' . $extension;
//            header("Content-Disposition: inline; filename=\"$file_name\"\n");
//            if ($options['save_eval'])
//                eval($options['save_eval']);
//            echo $output;
//            exit();
//        }
//        // END SAVING THE LIST ---
//        if ($options['center'])
//            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {
//
//                if (isset($_REQUEST['_openSIS_PDF']))
//                    echo " <TR><TD align=center>";
//            }
//
//        if ($options['count'] || $display_zero) {
//            if (($result_count == 0 || $display_count == 0) && $plural)
//                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
//            elseif ($result_count == 0 || $display_count == 0)
//                echo '<div class="alert alert-danger no-border">None were found.</div>';
//        }
//        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
//            if (!isset($_REQUEST['_openSIS_PDF'])) {
//                if (!$Request_page)
//                    $Request_page = 1;
//                if (!$_REQUEST['LO_direction'])
//                    $_REQUEST['LO_direction'] = 1;
//                $start = ($Request_page - 1) * $num_displayed + 1;
//                $stop = $start + ($num_displayed - 1);
//                if ($stop > $result_count)
//                    $stop = $result_count;
//
//                if ($result_count > $num_displayed) {
//
//                    echo $where_message = "<strong><br>
//									    $start through $stop</strong>";
//                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
//                    if (ceil($result_count / $num_displayed) <= 10) {
//                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
//                            if ($i != $Request_page) {
//                                if ($ForWindow == 'ForWindow') {
//                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
//                                } else {
//                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
//                                }
//                            } else {
//                                $pages .= "$i, ";
//                            }
//                        }
//                        $pages = substr($pages, 0, -2);
//                    } else {
//                        for ($i = 1; $i <= 7; $i++) {
//                            if ($i != $Request_page)
//                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
//                            else
//                                $pages .= "$i, ";
//                        }
//                        $pages = substr($pages, 0, -2) . " ... ";
//                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
//                            if ($i != $Request_page)
//                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
//                            else
//                                $pages .= "$i, ";
//                        }
//                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
//                    }
//                    echo $pages;
//
//                    echo '</strong></div>';
//                }
//            }
//            else {
//                $start = 1;
//                $stop = $result_count;
//                if ($cols > 8 || $_REQUEST['expanded_view']) {
//                    $_SESSION['orientation'] = 'landscape';
//                    $repeat_headers = 16;
//                } else
//                    $repeat_headers = 27;
//                if ($options['print']) {
//                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
//                    $html = $html[count($html) - 1];
//                    echo '</TD></TR></TABLE>';
//                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
//                    if ($br % 2 != 0) {
//                        $br++;
//                        echo '<BR>';
//                    }
//                } else
//                    echo '</TD></TR></TABLE>';
//            }
//            // END MISC ---
//            // WIDTH = 100%
//
//            echo '<div class="panel-heading">';
//            // SEARCH BOX & MORE HEADERS
//            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
//
//                echo "<h6 class=\"panel-title\">";
//                if ($singular && $plural && $options['count']) {
//                    if ($display_count > 1)
//                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
//                    elseif ($display_count == 1)
//                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
//                }
//                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
//                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";
//
//                echo '</h6>';
//                $colspan = 1;
//                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
//                    $_REQUEST['portal_search'] = 'true';
//                    $tmp_REQUEST = $_REQUEST;
//                    unset($tmp_REQUEST['LO_search']);
//                    unset($tmp_REQUEST['page']);
//                    echo "<div class=\"heading-elements\">";
//                    echo "<div class=\"heading-form\">";
//                    echo '<div class="form-group">';
//                    echo "<INPUT type=hidden id=hidden_field >";
//                    echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : '\' style=\'color:BBBBBB\''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
//                   // echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
//                   echo "<span class=input-group-btn><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
//                    echo '</div>'; //.input-group
//                    echo '</div>'; //.form-group
//                    echo '</div>'; //.heading-form
//                    echo "</div>"; //.heading-elements
//                    $colspan++;
//                }
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//            } else
//                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
//            // END SEARCH BOX ----
//            echo '</div>'; //.panel-heading
//            // SHADOW
//            if (!isset($_REQUEST['_openSIS_PDF'])) {
//
//                echo '<div id="pagerNavPosition"></div>';
//                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
//            }
//            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
//            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
//            echo '<THEAD>';
//            //if(!isset($_REQUEST['_openSIS_PDF']))
//            echo '<TR class="bg-grey-200">';
//
//            $i = 1;
//            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
//                //THIS LINE IS FOR COLUMN HEADING
//                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
//                $i++;
//            }
//
//            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
//                foreach ($column_names as $key => $value) {
//                    if ($_REQUEST['LO_sort'] == $key)
//                        $direction = -1 * $_REQUEST['LO_direction'];
//                    else
//                        $direction = 1;
//                    //THIS LINE IS FOR COLUMN HEADING
//                    echo "<TH><DIV id=LOx$i style='position: relative;'></DIV>";
//                    echo "<A class='text-grey-800'";
//                    if ($options['sort']) {
//                        if ($ForWindow == 'ForWindow') {
//                            echo "HREF=#";
//                        } else {
//                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
//                        }
//                    }
//                    echo ">$value</A>";
//                    if ($i == 1)
//                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
//                    echo "</TH>";
//                    $i++;
//                }
//
//                echo "</TR>";
//            }
//
//            $color = '';
//
//            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
//            echo '</THEAD><TBODY>';
//
//
//            // mab - enable add link as first or last
//            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {
//
//                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
//                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
//                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
//                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
//                elseif ($link['add']['html'] && $cols) {
//                    echo "<TR $color>";
//                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
//                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
//                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
//                        echo "<TD align=left>" . button('add') . "</TD>";
//
//                    foreach ($column_names as $key => $value) {
//                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
//                    }
//                    echo "</TR>";
//                    $count++;
//                }
//            }
//
//
//            for ($i = $start; $i <= $stop; $i++) {
//                $item = $result[$i];
//                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
//                    foreach ($item as $key => $value) {
//                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
//                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);
//
//                        if (strpos($value, 'LO_field') === false)
//                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
//                        else
//                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
//                    }
//                }
//
//                if ($item['row_color'])
//                    $color = $item['row_color'];
//                elseif ($color == '')
//                    $color = $side_color;
//                else
//                    $color = '';
//
//                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
//                    if ($count != 0) {
//                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
//                        echo '<!-- NEW PAGE -->';
//                    }
//                    echo "<TR>";
//                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
//                        echo "<TD></TD>";
//
//                    if ($cols) {
//                        foreach ($column_names as $key => $value) {
//                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
//                        }
//                    }
//                    echo "</TR>";
//                }
//                if ($count == 0)
//                    $count = $br;
//
//                echo "<TR $color>";
//                $count++;
//                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
//                    $button_title = $link['remove']['title'];
//
//                    $button_link = $link['remove']['link'];
//                    if (count($link['remove']['variables'])) {
//                        foreach ($link['remove']['variables'] as $var => $val)
//                            $button_link .= "&$var=" . ($item[$val]);
//                    }
//
//                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
//                }
//                if ($cols) {
//                    foreach ($column_names as $key => $value) {
//                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
//                            echo "<TD $color >";
//                            if ($key == 'FULL_NAME')
//                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
//                            if ($link[$key]['js'] === true) {
//                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
//                                if (count($link[$key]['variables'])) {
//                                    foreach ($link[$key]['variables'] as $var => $val)
//                                        echo "&$var=" . urlencode($item[$val]);
//                                }
//                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
//                                if ($link[$key]['extra'])
//                                    echo ' ' . $link[$key]['extra'];
//                                echo ">";
//                            }
//                            else {
//                                echo "<A HREF={$link[$key][link]}";
//                                if (count($link[$key]['variables'])) {
//                                    foreach ($link[$key]['variables'] as $var => $val)
//                                        echo "&$var=" . urlencode($item[$val]);
//                                }
//                                if ($link[$key]['extra'])
//                                    echo ' ' . $link[$key]['extra'];
//                                echo " onclick='grabA(this); return false;'>";
//                            }
//                            if ($color == Preferences('HIGHLIGHT'))
//                                echo '';
//                            else
//                                echo '<b>';
//                            echo $item[$key];
//                            echo '</b>';
//                            if (!$item[$key])
//                                echo '***';
//                            echo "</A>";
//                            if ($key == 'FULL_NAME')
//                                echo '</DIV>';
//                            echo "</TD>";
//                        }
//                        else {
//                            echo "<TD $color >";
//                            if ($key == 'FULL_NAME')
//                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
//                            if ($color == Preferences('HIGHLIGHT'))
//                                echo '';
//                            echo $item[$key];
//                            if (!$item[$key])
//                                echo '&nbsp;';
//                            if ($key == 'FULL_NAME')
//                                echo '<DIV>';
//                            echo "</TD>";
//                        }
//                    }
//                }
//                echo "</TR>";
//            }
//
//            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {
//
//                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
//                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
//                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
//                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
//                elseif ($link['add']['html'] && $cols) {
//                    if ($count % 2)
//                        $color = '';
//                    else
//                        $color = $side_color;
//
//                    echo "<TR $color>";
//                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
//                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
//                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
//                        echo "<TD align=left >" . button('add') . "</TD>";
//
//                    foreach ($column_names as $key => $value) {
//                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
//                    }
//                    echo "</TR>";
//                }
//            }
//            if ($result_count != 0) {
//                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                    echo '</TBODY>';
//                echo "</TABLE>";
//                // SHADOW
//                if (!isset($_REQUEST['_openSIS_PDF'])) {
//                    //echo '</TD ></TR></TABLE>';
//
//
//                    echo "<script language='javascript' type='text/javascript'>\n";
//
//                    
//                    echo "var pager = new Pager('results',$number_rec);\n";
//                    echo "pager.init();\n";
//                    echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
//                    echo "pager.showPage(1);\n";
//                    echo "</script>\n";
//                }
//
//                if ($options['center'])
//                    echo '';
//            }
//
//            // END PRINT THE LIST ---
//        }
//        if ($result_count == 0) {
//            // mab - problem with table closing if not opened above - do same conditional?
//            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
//                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
//                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
//                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
//                    $color = $side_color;
//
//                    if ($options['center'])
//
//                    // WIDTH=100%
//                    // SHADOW
//                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
//                    if ($link['add']['html']) {
//                        /* Here also change the colour for left corner */
//                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
//                        foreach ($column_names as $key => $value) {
//                            //Here to change the ListOutput Header Colour
//                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
//                        }
//                        echo "</TR>";
//
//                        echo "<TR >";
//
//                        if ($link['add']['html']['remove'])
//                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
//                        else
//                            echo "<TD>" . button('add') . "</TD>";
//
//                        foreach ($column_names as $key => $value) {
//                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
//                        }
//                        echo "</TR>";
//                        echo "</TABLE>";
//                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
//                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";
//
//                    // SHADOW
//
//                    echo "</TD></TR></TABLE>";
//                    if ($options['center'])
//                        echo '</CENTER>';
//                }
//        }
//        if ($result_count != 0) {
//            if ($options['yscroll']) {
//                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
//                echo '<TABLE cellpadding=6 id=LOy_table>';
//                $i = 1;
//
//                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
//                    $color = $side_color;
//                    foreach ($result as $item) {
//                        echo "<TR><TD $color  id=LO_row$i>";
//                        if ($color == Preferences('HIGHLIGHT'))
//                            echo '';
//                        echo $item['FULL_NAME'];
//                        if (!$item['FULL_NAME'])
//                            echo '&nbsp;';
//                        if ($color == Preferences('HIGHLIGHT'))
//                            echo '';
//                        echo "</TD></TR>";
//                        $i++;
//
//                        if ($item['row_color'])
//                            $color = $item['row_color'];
//                        elseif ($color == '')
//                            $color = $side_color;
//                        else
//                            $color = '';
//                    }
//                }
//                echo '</TABLE>';
//                echo '</div>';
//            }
//
//            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
//            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
//            $i = 1;
//            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
//                echo "<TD id=LO_col$i></TD>";
//                $i++;
//            }
//
//            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
//                foreach ($column_names as $key => $value) {
//                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
//                    $i++;
//                }
//            }
//            echo '</TR></TABLE>';
//            echo '</div>';
//        }
//    }
//}


function ListOutputExcel($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            $output = '';
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            foreach ($column_names as $ci => $cd) {
                $column_names_mod[$ci] = strip_tags($cd);
            }
//			if($options['save_delimiter']!='xml')
//			{
//				foreach($column_names_mod as $key=>$value)
//					$output .= str_replace('&nbsp;',' ',eregi_replace('<BR>',' ',ereg_replace('<!--.*-->','',$value))) . ($options['save_delimiter']=='comma'?',':"\t");
//				$output .= "\n";
//			}

            if ($options['save_delimiter'] != 'xml') {

                $output = '<table><tr>';
                foreach ($column_names_mod as $key => $value)
                    if ($key != 'CHECKBOX')
                        $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names_mod as $key => $value) {
                        if ($key != 'CHECKBOX')
                            $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

//            foreach ($result as $item) {
//                foreach ($column_names_mod as $key => $value) {
//                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
//                        $item[$key] = str_replace(',', ';', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
//                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
//                }
//                $output .= "\n";
//            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: text/plain");
            header("Content-Type: application/$extension");
            $file_name = ProgramTitle() . '.' . $extension;
            header("Content-Disposition: inline; filename=\"$file_name\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {

                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo "<div class=\"heading-form\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : '\' style=\'color:BBBBBB\''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    // echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo "<span class=input-group-btn><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo '</div>'; //.heading-form
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo '<TABLE id="results" class="table table-bordered table-striped" align=center>';
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))


            $i = 1;
//            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
//                //THIS LINE IS FOR COLUMN HEADING
//                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
//                $i++;
//            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                echo '<TR class="bg-grey-200">';
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TH><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo ">$value</A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TH>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
//                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                    echo '</TBODY>';
//                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            echo '</TBODY>';
            echo "</TABLE>";

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputNew($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_searchNew']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_searchNew'] && $_REQUEST['LO_searchNew'] != 'Search') {
                $_REQUEST['LO_searchNew'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_searchNew']);
                $_REQUEST['LO_searchNew'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        if (strtolower($_REQUEST['LO_searchNew']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {


                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags($val)) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            unset($output);
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
        #echo '<CENTER>';
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural) {
                echo '<div class="panel-body">';
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
                echo '</div>';
            } elseif ($result_count == 0 || $display_count == 0) {
                echo '<div class="panel-body">';
                echo '<div class="alert alert-danger no-border">None were found.</div>';
                echo '</div>';
            }
        }
        if ($result_count != 0 || ($_REQUEST['LO_searchNew'] && $_REQUEST['LO_searchNew'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_searchNew']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<INPUT type=text class='form-control'  id=LO_searchNew name=LO_searchNew value='" . (($_REQUEST['LO_searchNew'] && $_REQUEST['LO_searchNew'] != 'Search') ? $_REQUEST['LO_searchNew'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_searchNew=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_searchNew=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TH><DIV id=LOx$i style='position: relative;'></DIV></TH>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TH><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_searchNew=" . urlencode($_REQUEST['LO_searchNew']);
                        }
                    }
                    echo ">$value</A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TH>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutput_Medical($result, $column_names, $singular = '', $plural = '', $link = false, $dwnl, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;

    //$side_color = Preferences('COLOR');

    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags($val)) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            foreach ($result as $item) {
                foreach ($column_names as $key => $value) {
                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                        $item[$key] = str_replace(',', ';', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                }
                $output .= "\n";
            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"panel-body\"><div class=\"alert alert-danger no-border m-b-0\">No $plural were found.</div></div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="panel-body"><div class="alert alert-danger no-border m-b-0">None were found.</div></div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&dwnl=$dwnl&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                echo '</TD>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition"></div>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("<div onclick='[^']+'>", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputPrintReportMod($result, $column_names) {
    $table = '<table   cellpadding="6" width="100%" cellspacing="1" border="1 " style="border-collapse:collapse;white-space:nowrap;" align="center">';
    $table .= '<tbody>';
    $table .= '<tr>';
    foreach ($column_names as $key => $value) {
        $table .= '<td bgcolor="#d3d3d3">' . $value . '</td>';
    }
    $table .= '</tr>';
    foreach ($result as $res_key => $res_val) {
        $table .= '<tr>';
        foreach ($column_names as $key => $value) {

            $bg_color = ($res_key % 2 == 0 ? '#d3d3d3' : '#f5f5f5');

            $table .= '<td bgcolor="' . $bg_color . '">' . $res_val[$key] . '</td>';
        }
        $table .= '</tr>';
    }
    $table .= '</tbody></table';
    return $table;
}

function ListOutputMessagingGroups($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '', $custom_header = false) {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }
    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);
    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);


    //$PHP_tmp_SELF = PreparePHP_SELF($tmp_REQUEST);
    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;

    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            if ($options['save_delimiter'] != 'xml') {
                $output .= '<table><tr>';
                foreach ($column_names as $key => $value)
                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names as $key => $value) {
                        if ($key == 'ATTENDANCE' || $key == 'IGNORE_SCHEDULING')
                            $item[$key] = ($item[$key] == '<IMG SRC=assets/check.gif height=15>' ? 'Yes' : 'No');
                        $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

            if ($options['save_delimiter'] == 'xml') {
                foreach ($result as $item) {
                    foreach ($column_names as $key => $value) {
                        if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
                            $item[$key] = str_replace(',', ';', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
                        $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
                        $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
                    }
                    $output .= "\n";
                }
            }
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {

            if (($result_count == 0 || $display_count == 0) && $plural) {

                echo '<div class="panel-heading">';
                if ($custom_header != false)
                    echo $custom_header;
                echo '</div>';

                echo '<div class="panel-body">';
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
                echo '</div>';
            } elseif ($result_count == 0 || $display_count == 0) {

                echo '<div class="panel-heading">';
                if ($custom_header != false)
                    echo $custom_header;
                echo '</div>';

                echo '<div class="panel-body">';
                echo '<div class="alert alert-danger no-border">None were found.</div>';
                echo '</div>';
            }
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            if ($custom_header != false) {
                echo $custom_header;
            } else {

                // SEARCH BOX & MORE HEADERS
                if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                    echo "<h6 class=\"panel-title\">";
                    if ($singular && $plural && $options['count']) {
                        if ($display_count > 1)
                            echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                        elseif ($display_count == 1)
                            echo "<span class=\"heading-text\">1 $singular was found.</span>";
                    }
                    if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                        echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true class=\" btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                    echo '</h6>';
                    $colspan = 1;
                    if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                        $_REQUEST['portal_search'] = 'true';
                        $tmp_REQUEST = $_REQUEST;
                        unset($tmp_REQUEST['LO_search']);
                        unset($tmp_REQUEST['page']);
                        echo "<div class=\"heading-elements\">";
                        echo '<div class="form-group">';
                        echo "<INPUT type=hidden id=hidden_field >";
                        echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : ''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                        echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                        echo '</div>'; //.input-group
                        echo '</div>'; //.form-group
                        echo "</div>"; //.heading-elements
                        $colspan++;
                    }
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                } else {
                    echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
                }
            }
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }

            echo '<div class="table-responsive">';
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))
            echo '<TR class="bg-grey-200">';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<th><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</th>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class=\"table table-bordered table-striped\">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                echo '</div>';
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left>' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR>";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {


            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputStaffPrintSchoolInfo($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }

                unset($terms['of']);
                unset($terms['the']);
                unset($terms['a']);
                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {
                        $val = par_rep_cb('/[^a-zA-Z0-9 _]+/', '', strtolower($val));
                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {
                            if (ereg($term, $val))
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            foreach ($column_names as $ci => $cd) {
                $column_names_mod[$ci] = strip_tags($cd);
            }
//            if ($options['save_delimiter'] != 'xml') {
//                $output .='<table><tr>';
//                foreach ($column_names as $key => $value)
//                    $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
//                $output .='</tr>';
//                foreach ($result as $item) {
//                    $output .='<tr>';
//                    foreach ($column_names as $key => $value) {
//                        $output .='<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
//                    }
//                    $output .='</tr>';
//                }
//                $output .='</table>';
//            }
//            foreach ($result as $item) {
//                foreach ($column_names_mod as $key => $value) {
//                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
//                        $item[$key] = str_replace(',', ';', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
//                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
//                }
//                $output .= "\n";
//            }
            $output = '<table><tr><td>School</td><td>Profile</td><td>Start Date</td><td>End Date</td><td>Status</td></tr>';
            foreach ($result as $item) {
                $output .= '<tr>';
                $output .= '<td>' . $item['TITLE'] . '</td>';
                $output .= '<td>' . $item['PROFILE'] . '</td>';
                $output .= '<td>' . $item['START_DATE'] . '</td>';
                $output .= '<td>' . $item['END_DATE'] . '</td>';
                $output .= '<td>' . $item['ID'] . '</td>';
                $output .= '</tr>';
            }
            $output .= '</table>';
            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: application/$extension");
            header("Content-Disposition: inline; filename=\"" . ProgramTitle() . ".$extension\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---


            echo '<div class="panel-heading">';

            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {
                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo "&nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', $PHP_tmp_SELF) . "&$extra&LO_save=1&_openSIS_PDF=true ><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control' id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : 'Search\' style=\'color:BBBBBB\''), "' onfocus='if(this.value==\"Search\") this.value=\"\"; this.style.color=\"000000\";' onblur='if(this.value==\"\") {this.value=\"Search\"; this.style.color=\"BBBBBB\";}' onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo "<TABLE id='results' class=\"table table-bordered table-striped\" align=center>";
            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '<THEAD>';
            if (!isset($_REQUEST['_openSIS_PDF']))
                echo '<TR>';

            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                //THIS LINE IS FOR COLUMN HEADING
                echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV></TD>";
                $i++;
            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TD class=subtabs><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A ";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo " class=column_heading><b>$value</b></A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TD>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
                    echo '</TBODY>';
                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }
                echo "</TD ></TR>";
                echo "</TABLE>";

                if ($options['center'])
                    echo '';
            }

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

function ListOutputUnscheduleRequests($result, $column_names, $singular = '', $plural = '', $link = false, $group = false, $options = false, $ForWindow = '') {
    if (!isset($options['save']))
        $options['save'] = true;
    if (!isset($options['print']))
        $options['print'] = true;
    if (!isset($options['search']))
        $options['search'] = true;
    if (!isset($options['center']))
        $options['center'] = true;
    if (!isset($options['count']))
        $options['count'] = true;
    if (!isset($options['sort']))
        $options['sort'] = true;
    if (!$link)
        $link = array();

    if (isset($_REQUEST['page'])) {
        if ($_REQUEST['list_type'] == $singular) {
            $Request_page = $_REQUEST['page'];
        }
    }

    if (!isset($options['add'])) {
        if (!AllowEdit() || $_REQUEST['_openSIS_PDF']) {
            if ($link) {
                unset($link['add']);
                unset($link['remove']);
            }
        }
    }

    // PREPARE LINKS ---
    $result_count = $display_count = count($result);
    $num_displayed = 100000;
    $extra = "page=$_REQUEST[page]&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']);

    $tmp_REQUEST = $_REQUEST;
    unset($tmp_REQUEST['page']);
    unset($tmp_REQUEST['LO_sort']);
    unset($tmp_REQUEST['LO_direction']);
    unset($tmp_REQUEST['LO_search']);
    unset($tmp_REQUEST['remove_prompt']);
    unset($tmp_REQUEST['remove_name']);
    unset($tmp_REQUEST['LO_save']);
    unset($tmp_REQUEST['PHPSESSID']);



    $PHP_tmp_SELF = str_replace('>', '', PreparePHP_SELF($tmp_REQUEST));
    // END PREPARE LINKS ---
    // UN-GROUPING
    $group_count = count($group);
    if (!is_array($group))
        $group_count = false;



    $side_color = '';

    if ($group_count && $result_count) {
        $color = '';
        $group_result = $result;
        unset($result);
        $result[0] = '';

        foreach ($group_result as $item1) {
            if ($group_count == 1) {
                if ($color == '')
                    $color = $side_color;
                else
                    $color = '';
            }

            foreach ($item1 as $item2) {
                if ($group_count == 1) {
                    $i++;
                    if (count($group[0]) && $i != 1) {
                        foreach ($group[0] as $column)
                            $item2[$column] = str_replace('<!-- <!--', '<!--', '<!-- ' . str_replace('-->', '--><!--', $item2[$column])) . ' -->';
                    }
                    $item2['row_color'] = $color;
                    $result[] = $item2;
                } else {
                    if ($group_count == 2) {
                        if ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }

                    foreach ($item2 as $item3) {
                        if ($group_count == 2) {
                            $i++;
                            if (count($group[0]) && $i != 1) {
                                foreach ($group[0] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            if (count($group[1]) && $i != 1) {
                                foreach ($group[1] as $column)
                                    $item3[$column] = '<!-- ' . $item3[$column] . ' -->';
                            }
                            $item3['row_color'] = $color;
                            $result[] = $item3;
                        } else {
                            if ($group_count == 3) {
                                if ($color == '')
                                    $color = $side_color;
                                else
                                    $color = '';
                            }

                            foreach ($item3 as $item4) {
                                if ($group_count == 3) {
                                    $i++;
                                    if (count($group[2]) && $i != 1) {
                                        foreach ($group[2] as $column)
                                            unset($item4[$column]);
                                    }
                                    $item4['row_color'] = $color;
                                    $result[] = $item4;
                                }
                            }
                        }
                    }
                }
            }
            $i = 0;
        }
        unset($result[0]);
        $result_count = count($result);

        unset($_REQUEST['LO_sort']);
    }
    // END UN-GROUPING
    $_LIST['output'] = true;


    // PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
    if ($_LIST['output'] != false) {
        if ($result_count != 0) {
            $count = 0;
            $remove = count($link['remove']['variables']);
            $cols = count($column_names);

            // HANDLE SEARCHES ---
            if ($result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') {
                $_REQUEST['LO_search'] = $search_term = str_replace('\\\"', '"', $_REQUEST['LO_search']);
                $_REQUEST['LO_search'] = $search_term = par_rep_cb('/[^a-zA-Z0-9 _"]*/', '', strtolower($search_term));

                if (substr($search_term, 0, 0) != '"' && substr($search_term, -1) != '"') {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    while ($space_pos = strpos($search_term, ' ')) {
                        $terms[strtolower(substr($search_term, 0, $space_pos))] = 1;
                        $search_term = substr($search_term, ($space_pos + 1));
                    }
                    $terms[trim($search_term)] = 1;
                } else {
                    $search_term = par_rep_cb('/"/', '', $search_term);
                    $terms[trim($search_term)] = 1;
                }
                $t_in = array_keys($terms);

                unset($t_in);
                unset($terms['of']);
                unset($terms['the']);

                unset($terms['an']);
                unset($terms['in']);

                foreach ($result as $key => $value) {
                    $values[$key] = 0;
                    foreach ($value as $name => $val) {

                        if (strtolower($_REQUEST['LO_search']) == $val)
                            $values[$key] += 25;
                        foreach ($terms as $term => $one) {

                            $search_q_res = DBGet(DBQuery('SELECT COUNT(1) AS RES FROM (SELECT \'c\') as Y WHERE \'' . strtolower(strip_tags(str_replace("'", "''", $val))) . '\' like \'%' . $term . '%\' '));
                            if ($search_q_res[1]['RES'] != 0)
                                $values[$key] += 3;
                        }
                    }
                    if ($values[$key] == 0) {
                        unset($values[$key]);
                        unset($result[$key]);
                        $result_count--;
                        $display_count--;
                    }
                }
                if ($result_count) {
                    array_multisort($values, SORT_DESC, $result);
                    $result = ReindexResults($result);
                    $values = ReindexResults($values);

                    $last_value = 1;
                    $scale = (100 / $values[$last_value]);

                    for ($i = $last_value; $i <= $result_count; $i++)
                        $result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i] * $scale)) . '--><IMG SRC="assets/pixel_grey.gif" width=' . ((int) ($values[$i] * $scale)) . ' height=10>';
                }
                $column_names['RELEVANCE'] = "Relevance";

                if (is_array($group) && count($group)) {
                    $options['count'] == false;
                    $display_zero = true;
                }
            }

            // END SEARCHES ---

            if ($_REQUEST['LO_sort']) {
                foreach ($result as $sort) {
                    if (substr($sort[$_REQUEST['LO_sort']], 0, 4) != '<!--')
                        $sort_array[] = $sort[$_REQUEST['LO_sort']];
                    else
                        $sort_array[] = substr($sort[$_REQUEST['LO_sort']], 4, strpos($sort[$_REQUEST['LO_sort']], '-->') - 5);
                }
                if ($_REQUEST['LO_direction'] == -1)
                    $dir = SORT_DESC;
                else
                    $dir = SORT_ASC;

                if ($result_count > 1) {
                    if (is_int($sort_array[1]) || is_double($sort_array[1]))
                        array_multisort($sort_array, $dir, SORT_NUMERIC, $result);
                    elseif (VerifyDate_sort($sort_array[1]))
                        array_multisort(date_to_timestamp($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'POINTS')
                        array_multisort(point_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'PERCENT' || $_REQUEST['LO_sort'] == 'LETTER_GRADE' || $_REQUEST['LO_sort'] == 'GRADE_PERCENT')
                        array_multisort(percent_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR1')
                        array_multisort(range_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    elseif ($_REQUEST['LO_sort'] == 'BAR2')
                        array_multisort(rank_to_number($sort_array), $dir, SORT_NUMERIC, $result);
                    else
                        array_multisort($sort_array, $dir, $result);
                    for ($i = $result_count - 1; $i >= 0; $i--)
                        $result[$i + 1] = $result[$i];
                    unset($result[0]);
                }
            }
        }
        // HANDLE SAVING THE LIST ---

        if ($_REQUEST['LO_save'] == '1') {
            $output = '';
            if (!$options['save_delimiter'] && Preferences('DELIMITER') == 'CSV')
                $options['save_delimiter'] = 'comma';
            switch ($options['save_delimiter']) {
                case 'comma':
                    $extension = 'csv';
                    break;
                case 'xml':
                    $extension = 'xml';
                    break;
                default:
                    $extension = 'xls';
                    break;
            }
            ob_end_clean();

            foreach ($column_names as $ci => $cd) {
                $column_names_mod[$ci] = strip_tags($cd);
            }
//			if($options['save_delimiter']!='xml')
//			{
//				foreach($column_names_mod as $key=>$value)
//					$output .= str_replace('&nbsp;',' ',eregi_replace('<BR>',' ',ereg_replace('<!--.*-->','',$value))) . ($options['save_delimiter']=='comma'?',':"\t");
//				$output .= "\n";
//			}

            if ($options['save_delimiter'] != 'xml') {

                $output = '<table><tr>';
                foreach ($column_names_mod as $key => $value)
                    if ($key != 'CHECKBOX')
                        $output .= '<td>' . str_replace('&nbsp;', ' ', par_rep_cb('/<BR>/', ' ', par_rep_cb('/<!--.*-->/', '', $value))) . '</td>';
                $output .= '</tr>';
                foreach ($result as $item) {
                    $output .= '<tr>';
                    foreach ($column_names_mod as $key => $value) {
                        if ($key != 'CHECKBOX')
                            $output .= '<td>' . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . '</td>';
                    }
                    $output .= '</tr>';
                }
                $output .= '</table>';
            }

//            foreach ($result as $item) {
//                foreach ($column_names_mod as $key => $value) {
//                    if ($options['save_delimiter'] == 'comma' && !$options['save_quotes'])
//                        $item[$key] = str_replace(',', ';', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $item[$key]);
//                    $item[$key] = par_rep_cb('/<SELECT.*</SELECT\>/', '', $item[$key]);
//                    $output .= ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'xml' ? '<' . str_replace(' ', '', $value) . '>' : '') . par_rep_cb('/<[^>]+>/', '', par_rep_cb("/<div onclick='[^']+'>/", '', par_rep_cb('/ +/', ' ', par_rep_cb('/&[^;]+;/', '', str_replace('<BR>&middot;', ' : ', str_replace('&nbsp;', ' ', $item[$key])))))) . ($options['save_delimiter'] == 'xml' ? '</' . str_replace(' ', '', $value) . '>' . "\n" : '') . ($options['save_quotes'] ? '"' : '') . ($options['save_delimiter'] == 'comma' ? ',' : "\t");
//                }
//                $output .= "\n";
//            }

            header("Cache-Control: public");
            header("Pragma: ");
            header("Content-Type: text/plain");
            header("Content-Type: application/$extension");
            $file_name = ProgramTitle() . '.' . $extension;
            header("Content-Disposition: inline; filename=\"$file_name\"\n");
            if ($options['save_eval'])
                eval($options['save_eval']);
            echo $output;
            exit();
        }
        // END SAVING THE LIST ---
        if ($options['center'])
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0)))) {

                if (isset($_REQUEST['_openSIS_PDF']))
                    echo " <TR><TD align=center>";
            }

        if ($options['count'] || $display_zero) {
            if (($result_count == 0 || $display_count == 0) && $plural)
                echo "<div class=\"alert alert-danger no-border\">No $plural were found.</div>";
            elseif ($result_count == 0 || $display_count == 0)
                echo '<div class="alert alert-danger no-border">None were found.</div>';
        }
        if ($result_count != 0 || ($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search')) {
            if (!isset($_REQUEST['_openSIS_PDF'])) {
                if (!$Request_page)
                    $Request_page = 1;
                if (!$_REQUEST['LO_direction'])
                    $_REQUEST['LO_direction'] = 1;
                $start = ($Request_page - 1) * $num_displayed + 1;
                $stop = $start + ($num_displayed - 1);
                if ($stop > $result_count)
                    $stop = $result_count;

                if ($result_count > $num_displayed) {

                    echo $where_message = "<strong><br>
									    $start through $stop</strong>";
                    echo "<div style=text-align:right;margin-top:-14px;padding-right:15px><strong>Go to Page ";
                    if (ceil($result_count / $num_displayed) <= 10) {
                        for ($i = 1; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page) {
                                if ($ForWindow == 'ForWindow') {
                                    $pages .= "<A HREF=" . str_replace('Modules.php', 'ForWindow.php', $PHP_tmp_SELF) . "&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                } else {
                                    $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i&list_type=$singular>$i</A>, ";
                                }
                            } else {
                                $pages .= "$i, ";
                            }
                        }
                        $pages = substr($pages, 0, -2);
                    } else {
                        for ($i = 1; $i <= 7; $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " ... ";
                        for ($i = ceil($result_count / $num_displayed) - 2; $i <= ceil($result_count / $num_displayed); $i++) {
                            if ($i != $Request_page)
                                $pages .= "<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=$i>$i</A>, ";
                            else
                                $pages .= "$i, ";
                        }
                        $pages = substr($pages, 0, -2) . " &nbsp;<A HREF=$PHP_tmp_SELF&LO_sort=$_REQUEST[LO_sort]&LO_direction=$_REQUEST[LO_direction]&LO_search=" . urlencode($_REQUEST['LO_search']) . "&page=" . ($Request_page + 1) . ">Next Page</A><BR>";
                    }
                    echo $pages;

                    echo '</strong></div>';
                }
            }
            else {
                $start = 1;
                $stop = $result_count;
                if ($cols > 8 || $_REQUEST['expanded_view']) {
                    $_SESSION['orientation'] = 'landscape';
                    $repeat_headers = 16;
                } else
                    $repeat_headers = 27;
                if ($options['print']) {
                    $html = explode('<!-- new page -->', strtolower(ob_get_contents()));
                    $html = $html[count($html) - 1];
                    echo '</TD></TR></TABLE>';
                    $br = (substr_count($html, '<br>')) + (substr_count($html, '</p>')) + (substr_count($html, '</tr>')) + (substr_count($html, '</h1>')) + (substr_count($html, '</h2>')) + (substr_count($html, '</h3>')) + (substr_count($html, '</h4>')) + (substr_count($html, '</h5>'));
                    if ($br % 2 != 0) {
                        $br++;
                        echo '<BR>';
                    }
                } else
                    echo '</TD></TR></TABLE>';
            }
            // END MISC ---
            // WIDTH = 100%

            echo '<div class="panel-heading">';
            // SEARCH BOX & MORE HEADERS
            if ($where_message || ($singular && $plural) || (!isset($_REQUEST['_openSIS_PDF']) && $options['search'])) {

                echo "<h6 class=\"panel-title\">";
                if ($singular && $plural && $options['count']) {
                    if ($display_count > 1)
                        echo "<span class=\"heading-text\">$display_count $plural were found.</span>";
                    elseif ($display_count == 1)
                        echo "<span class=\"heading-text\">1 $singular was found.</span>";
                }
                if ($options['save'] && !isset($_REQUEST['_openSIS_PDF']) && $result_count > 0)
                    echo " &nbsp; <A HREF=" . str_replace('Modules.php', 'ForExport.php', 'Modules.php?modname=scheduling/UnfilledRequests.php&search_modfunc=list&next_modname=scheduling/UnfilledRequests.php') . "&$extra&LO_save=1&_openSIS_PDF=true  class=\"btn btn-success btn-xs btn-icon text-white\" data-popup=\"tooltip\" data-placement=\"top\" data-container=\"body\" data-original-title=\"Download Spreadsheet\" title=\"Download Spreadsheet\"><i class=\"icon-file-excel\"></i></a>";

                echo '</h6>';
                $colspan = 1;
                if (!isset($_REQUEST['_openSIS_PDF']) && $options['search']) {
                    $_REQUEST['portal_search'] = 'true';
                    $tmp_REQUEST = $_REQUEST;
                    unset($tmp_REQUEST['LO_search']);
                    unset($tmp_REQUEST['page']);
                    echo "<div class=\"heading-elements\">";
                    echo "<div class=\"heading-form\">";
                    echo '<div class="form-group">';
                    echo "<INPUT type=hidden id=hidden_field >";
                    echo "<div class=\"input-group\"><INPUT type=text class='form-control'  id=LO_search name=LO_search value='" . (($_REQUEST['LO_search'] && $_REQUEST['LO_search'] != 'Search') ? $_REQUEST['LO_search'] : '\' style=\'color:BBBBBB\''), "' placeholder=\"Search\" onKeyUp='fill_hidden_field(\"hidden_field\",this.value)' onkeypress='if(event.keyCode==13){document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value; return false;} '>";
                    // echo "<span class=\"input-group-btn\"><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo "<span class=input-group-btn><INPUT type=button class='btn btn-primary' value=Go onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&LO_search=\"+document.getElementById(\"hidden_field\").value;'></span>";
                    echo '</div>'; //.input-group
                    echo '</div>'; //.form-group
                    echo '</div>'; //.heading-form
                    echo "</div>"; //.heading-elements
                    $colspan++;
                }
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            } else
                echo '<DIV id=LOx' . (count($column_names) + (($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0) + (($remove && !isset($_REQUEST['_openSIS_PDF'])) ? 1 : 0)) . ' style="width:0; position: relative; height:0;"></DIV>';
            // END SEARCH BOX ----
            echo '</div>'; //.panel-heading
            // SHADOW
            if (!isset($_REQUEST['_openSIS_PDF'])) {

                echo '<div id="pagerNavPosition"></div>';
                //echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
            }
            echo '<TABLE id="results" class="table table-bordered table-striped" align=center>';
            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '<THEAD>';
            //if(!isset($_REQUEST['_openSIS_PDF']))


            $i = 1;
//            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
//                //THIS LINE IS FOR COLUMN HEADING
//                echo "<th><DIV id=LOx$i style='position: relative;'></DIV></th>";
//                $i++;
//            }

            if ($result_count != 0 && $cols && !isset($_REQUEST['_openSIS_PDF'])) {
                echo '<TR class="bg-grey-200">';
                foreach ($column_names as $key => $value) {
                    if ($_REQUEST['LO_sort'] == $key)
                        $direction = -1 * $_REQUEST['LO_direction'];
                    else
                        $direction = 1;
                    //THIS LINE IS FOR COLUMN HEADING
                    echo "<TH><DIV id=LOx$i style='position: relative;'></DIV>";
                    echo "<A class='text-grey-800'";
                    if ($options['sort']) {
                        if ($ForWindow == 'ForWindow') {
                            echo "HREF=#";
                        } else {
                            echo "HREF=$PHP_tmp_SELF&page=$_REQUEST[page]&LO_sort=$key&LO_direction=$direction&LO_search=" . urlencode($_REQUEST['LO_search']);
                        }
                    }
                    echo ">$value</A>";
                    if ($i == 1)
                        echo "<DIV id=LOy0 style='position: relative;'></DIV>";
                    echo "</TH>";
                    $i++;
                }

                echo "</TR>";
            }

            $color = '';

            //if(!isset($_REQUEST['_openSIS_PDF']) && ($stop-$start)>10)
            echo '</THEAD><TBODY>';


            // mab - enable add link as first or last
            if ($result_count != 0 && $link['add']['first'] && ($stop - $start) >= $link['add']['first']) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left>" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left >" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                    $count++;
                }
            }


            for ($i = $start; $i <= $stop; $i++) {
                $item = $result[$i];
                if (isset($_REQUEST['_openSIS_PDF']) && $options['print'] && count($item)) {
                    foreach ($item as $key => $value) {
                        $value = par_rep_cb('/<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>/', '\\1', $value);
                        $value = par_rep_cb('/<SELECT.*</SELECT\>/', '', $value);

                        if (strpos($value, 'LO_field') === false)
                            $item[$key] = str_replace(' ', '&nbsp;', par_rep_cb("/<div onclick='[^']+'>/", '', $value));
                        else
                            $item[$key] = par_rep_cb("/<div onclick='[^']+'>/", '', $value);
                    }
                }

                if ($item['row_color'])
                    $color = $item['row_color'];
                elseif ($color == '')
                    $color = $side_color;
                else
                    $color = '';

                if (isset($_REQUEST['_openSIS_PDF']) && $count % $repeat_headers == 0) {
                    if ($count != 0) {
                        echo '</TABLE><TABLE class="table table-bordered table-striped">';
                        echo '<!-- NEW PAGE -->';
                    }
                    echo "<TR>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD></TD>";

                    if ($cols) {
                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . str_replace(' ', '&nbsp;', $value) . "</TD>";
                        }
                    }
                    echo "</TR>";
                }
                if ($count == 0)
                    $count = $br;

                echo "<TR $color>";
                $count++;
                if ($remove && !isset($_REQUEST['_openSIS_PDF'])) {
                    $button_title = $link['remove']['title'];

                    $button_link = $link['remove']['link'];
                    if (count($link['remove']['variables'])) {
                        foreach ($link['remove']['variables'] as $var => $val)
                            $button_link .= "&$var=" . ($item[$val]);
                    }

                    echo "<TD $color>" . button('remove', $button_title, $button_link) . "</TD>";
                }
                if ($cols) {
                    foreach ($column_names as $key => $value) {
                        if ($link[$key] && !isset($_REQUEST['_openSIS_PDF'])) {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . ' style="height: 100%; min-height: 100%; position: relative;">';
                            if ($link[$key]['js'] === true) {
                                echo "<A HREF=# onclick='window.open(\"{$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                echo "\",\"\",\"scrollbars=yes,resizable=yes,width=800,height=400\");'";
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo ">";
                            }
                            else {
                                echo "<A HREF={$link[$key][link]}";
                                if (count($link[$key]['variables'])) {
                                    foreach ($link[$key]['variables'] as $var => $val)
                                        echo "&$var=" . urlencode($item[$val]);
                                }
                                if ($link[$key]['extra'])
                                    echo ' ' . $link[$key]['extra'];
                                echo " onclick='grabA(this); return false;'>";
                            }
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            else
                                echo '<b>';
                            echo $item[$key];
                            echo '</b>';
                            if (!$item[$key])
                                echo '***';
                            echo "</A>";
                            if ($key == 'FULL_NAME')
                                echo '</DIV>';
                            echo "</TD>";
                        }
                        else {
                            echo "<TD $color >";
                            if ($key == 'FULL_NAME')
                                echo '<DIV id=LOy' . ($count - $br) . '  style="position: relative;">';
                            if ($color == Preferences('HIGHLIGHT'))
                                echo '';
                            echo $item[$key];
                            if (!$item[$key])
                                echo '&nbsp;';
                            if ($key == 'FULL_NAME')
                                echo '<DIV>';
                            echo "</TD>";
                        }
                    }
                }
                echo "</TR>";
            }

            if ($result_count != 0 && (!$link['add']['first'] || $link['add']['first'] && ($stop - $start) < $link['add']['first'])) {

                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add', $link['add']['title'], $link['add']['link']) . "</TD></TR>";
                elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo "<TR><TD colspan=" . ($remove ? $cols + 1 : $cols) . " align=left>" . button('add') . $link['add']['span'] . "</TD></TR>";
                elseif ($link['add']['html'] && $cols) {
                    if ($count % 2)
                        $color = '';
                    else
                        $color = $side_color;

                    echo "<TR $color>";
                    if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $link['add']['html']['remove'])
                        echo "<TD align=left>" . $link['add']['html']['remove'] . "</TD>";
                    elseif ($remove && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TD align=left >" . button('add') . "</TD>";

                    foreach ($column_names as $key => $value) {
                        echo "<TD align=left  valign=top>" . $link['add']['html'][$key] . "</TD>";
                    }
                    echo "</TR>";
                }
            }
            if ($result_count != 0) {
//                if (!isset($_REQUEST['_openSIS_PDF']) && ($stop - $start) > 10)
//                    echo '</TBODY>';
//                echo "</TABLE>";
                // SHADOW
                if (!isset($_REQUEST['_openSIS_PDF'])) {
                    //echo '</TD ></TR></TABLE>';


                    $number_rec = 100;                    
                    if ($result_count > $number_rec) {
                        echo "<script language='javascript' type='text/javascript'>\n";
                        echo '$(function(){if($("#results").length>0){';
                        
                        echo "var pager = new Pager('results',$number_rec);\n";
                        echo "pager.init();\n";
                        echo "pager.showPageNav('pager', 'pagerNavPosition');\n";
                        echo "pager.showPage(1);\n";
                        echo '}});';
                        echo "</script>\n";
                    }
                }

                if ($options['center'])
                    echo '';
            }

            echo '</TBODY>';
            echo "</TABLE>";

            // END PRINT THE LIST ---
        }
        if ($result_count == 0) {
            // mab - problem with table closing if not opened above - do same conditional?
            if (($result_count > $num_displayed) || (($options['count'] || $display_zero) && ((($result_count == 0 || $display_count == 0) && $plural) || ($result_count == 0 || $display_count == 0))))
                if ($link['add']['link'] && !isset($_REQUEST['_openSIS_PDF']))
                    echo '<table cellspacing=8 cellpadding=6 ><tr><TD align=left >' . button('add', $link['add']['title'], $link['add']['link']) . '</td></tr></table>';
                elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;

                    if ($options['center'])

                    // WIDTH=100%
                    // SHADOW
                        echo '<TABLE width=100% cellpadding=0 cellspacing=0><TR><TD align=center>';
                    if ($link['add']['html']) {
                        /* Here also change the colour for left corner */
                        echo "<TABLE class=\"table table-bordered table-striped\"><TR><TD class=subtabs></TD>";
                        foreach ($column_names as $key => $value) {
                            //Here to change the ListOutput Header Colour
                            echo "<TD class=subtabs><A><b>" . $value . "</b></A></TD>";
                        }
                        echo "</TR>";

                        echo "<TR >";

                        if ($link['add']['html']['remove'])
                            echo "<TD >" . $link['add']['html']['remove'] . "</TD>";
                        else
                            echo "<TD>" . button('add') . "</TD>";

                        foreach ($column_names as $key => $value) {
                            echo "<TD >" . $link['add']['html'][$key] . "</TD>";
                        }
                        echo "</TR>";
                        echo "</TABLE>";
                    } elseif ($link['add']['span'] && !isset($_REQUEST['_openSIS_PDF']))
                        echo "<TABLE><TR><TD align=center>" . button('add') . $link['add']['span'] . "</TD></TR></TABLE>";

                    // SHADOW

                    echo "</TD></TR></TABLE>";
                    if ($options['center'])
                        echo '</CENTER>';
                }
        }
        if ($result_count != 0) {
            if ($options['yscroll']) {
                echo '<div id="LOy_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
                echo '<TABLE cellpadding=6 id=LOy_table>';
                $i = 1;

                if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                    $color = $side_color;
                    foreach ($result as $item) {
                        echo "<TR><TD $color  id=LO_row$i>";
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo $item['FULL_NAME'];
                        if (!$item['FULL_NAME'])
                            echo '&nbsp;';
                        if ($color == Preferences('HIGHLIGHT'))
                            echo '';
                        echo "</TD></TR>";
                        $i++;

                        if ($item['row_color'])
                            $color = $item['row_color'];
                        elseif ($color == '')
                            $color = $side_color;
                        else
                            $color = '';
                    }
                }
                echo '</TABLE>';
                echo '</div>';
            }

            echo '<div id="LOx_layer" style="position: absolute; top: 0; left: 0; visibility:hidden;">';
            echo '<TABLE cellpadding=6 id=LOx_table><TR>';
            $i = 1;
            if ($remove && !isset($_REQUEST['_openSIS_PDF']) && $result_count != 0) {
                echo "<TD id=LO_col$i></TD>";
                $i++;
            }

            if ($cols && !isset($_REQUEST['_openSIS_PDF'])) {
                foreach ($column_names as $key => $value) {
                    echo '<TD id=LO_col' . $i . '><A class=column_heading><b>' . str_replace('controller', '', $value) . '</b></A></TD>';
                    $i++;
                }
            }
            echo '</TR></TABLE>';
            echo '</div>';
        }
    }
}

?>
