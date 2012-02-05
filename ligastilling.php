<?php
/*
Plugin Name: WP-Ligastilling
Plugin URI: http://kasperhartwich.dk
Description: Viser ligastilling, hentet fra bold.dk - Læs koden for mere info.
Date: 2011-10-18
Author: Kasper Hartwich
Author URI: http://kasperhartwich.dk
Version: 0.4beta
*/

require_once('simple_html_dom.php');
add_filter('the_content','liga_content');
#add_filter('the_content','kampe_content');
add_filter('the_content','hold_content');

// Hvis du ønsker andre titler for tabellen, kan du erstatte dem her.
$titles = array('#', 'Klub', 'K', 'V', 'U', 'T', 'Mål', 'Point');
$player_titles = array('Navn', 'Nationalitet', 'Fødselsdato');

function hold_content($content) {

  global $titles;
  preg_match_all('/\[hold(.*?)]/i', $content, $matches );

  for($x=0; $x<count($matches[0]); $x++) {

    $players = get_players($matches[1][0]);

    $html = '<table class="hold">';

    $html .= '<thead>';
    $html .= '<tr class="headline">';
    
    $html .= '<th class="name">' . $player_titles[0] . '</th>';
    $html .= '<th class="country">' . $player_titles[1] . '</th>';
    $html .= '<th class="birth">' . $player_titles[2] . '</th>';
  
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ($players as $row) {
    
      $html .= '<tr>';
      
      $html .= '<td class="name">' . $row['name'] . '</td>';
      $html .= '<td class="country country_' . strtolower(str_replace(array(' ', '.'), '', $row['country'])) . '">' . $row['country'] . '</td>';
      $html .= '<td class="birth">' . $row['birth'] . '</td>';

      $html .= '</tr>';
  
    }

    $html .= '</tbody>';
    $html .= '</table>';

    $content = str_replace($matches[0][$x], $html, $content);
  }
  return $content;
}

function liga_content($content) {

  global $titles;
  preg_match_all('/\[liga(.*?)]/i', $content, $matches );

  for($x=0; $x<count($matches[0]); $x++) {

    $table = get_table($matches[1][0]);

    $html = '<table class="ligastilling">';
    
    unset($table[0]); // Remove first row and replace with our own headlines.
    array_pop($table);

    $html .= '<thead>';
    $html .= '<tr class="headline">';
    
    $html .= '<th class="position">' . $titles[0] . '</th>';
    $html .= '<th class="club">' . $titles[1] . '</th>';
    $html .= '<th class="matches">' . $titles[2] . '</th>';
    $html .= '<th class="won">' . $titles[3] . '</th>';
    $html .= '<th class="tie">' . $titles[4] . '</th>';
    $html .= '<th class="lost">' . $titles[5] . '</th>';
    $html .= '<th class="goalscore">' . $titles[6] . '</th>';
    $html .= '<th class="points">' . $titles[7] . '</th>';
  
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($table as $row) {
    
      if ($row[0]>0) {

        $html .= '<tr class="pos' . trim($row[0]) . ' club_' . strtolower(str_replace(array(' ', '.'), '', $row[1])) . '">';
        
        $html .= '<td class="position">' . $row[0] . '</td>';
        $html .= '<td class="club">' . $row[1] . '</td>';
        $html .= '<td class="matches">' . $row[2] . '</td>';
        $html .= '<td class="won">' . $row[3] . '</td>';
        $html .= '<td class="tie">' . $row[4] . '</td>';
        $html .= '<td class="lost">' . $row[5] . '</td>';
        $html .= '<td class="goalscore">' . $row[6] . '</td>';
        $html .= '<td class="points">' . $row[7] . '</td>';

        $html .= '</tr>';
    
      }

    }

    $html .= '</tbody>';
    $html .= '</table>';

    $content = str_replace($matches[0][$x], $html, $content);
  }
  return $content;
}

function get_table($url) {
  /*
  This function gets the tableresults from bold.dk
  */
  $url = trim($url)!='' ? trim($url) : "http://www.bold.dk/fodbold/England/Premier_League"; 
  $html = new simple_html_dom(); 
  $html->load_file($url);

  $output = array();
  $i=0;

  foreach($html->find('div[id=tabsamlet] table[class=ligatable] tr') as $tr) {
    $output[$i] = array();
    foreach($tr->find('td') as $td) {
      if ($td->plaintext!='') {
        $output[$i][] =  utf8_encode($td->plaintext);
      }
    }
    $i++;
  }
  return $output;
}


function get_players($url) {
  /*
  This function gets the players from bold.dk
  */
  $url = trim($url)!='' ? trim($url) : "http://www.bold.dk/fodbold/England/Premier_League";
  $html = new simple_html_dom(); 
  $html->load_file($url);

  $output = array();
  $i=0;

  foreach($html->find('table[id=clubplayerstable] tbody tr') as $tr) {

    $name_array = explode(',',$tr->find('span',0)->plaintext);
    $player['name'] = utf8_encode(implode(' ', array_reverse($name_array)));

    $data = utf8_encode(strip_tags($tr->find('div',0)->innertext));
    $data = str_replace(array('Født: ', 'Land: '),'|',$data);
    $data = str_replace(array('ukendt'),'',$data);
    $data = explode('|',$data);
    $player['country'] = $data[0];
    $player['birth'] = $data[1];
    $output[$player['name']] = $player;
    $i++;

  }
  ksort($output);
  return $output;
}

function get_games($url) {
  /*
  This function gets the games from bold.dk
  */
  $url = trim($url)!='' ? trim($url) : "http://www.bold.dk/fodbold/England/Premier_League"; 
  $html = new simple_html_dom(); 
  $html->load_file($url . '/program');


  $output = array();
  $i=0;

  foreach($html->find('table[id=tabsamlet] table[class=ligatable] tr') as $tr) {
    $output[$i] = array();
    foreach($tr->find('td') as $td) {
      if ($td->plaintext!='') {
        $output[$i][] =  utf8_encode($td->plaintext);
      }
    }
    $i++;
  }
  return $output;
}
