<?php

// Math Functions
function calculate_average($price_array) {
  if (is_array($price_array)) {
    if (array_sum($price_array) > 0) {
      return number_format((float)round((array_sum($price_array) / count($price_array)), 3), 3, '.', '');
    } else {
      return "<span class='nan'></span>";
    }
  } else {
    return "<span class='nan'></span>";
  }
}

function calculate_variance($price_array) {
  if (is_array($price_array)) {
    return round(((max($price_array) - min($price_array)) / (array_sum($price_array) / count($price_array)) * 100), 3);
  } else {
    return "<span class='nan'></span>";
  }
}

function calculate_difference($price_1, $price_2, $direction = 'standard') {
  if ($price_1 > 0 && $price_2 > 0) {
    if ($direction == 'standard') {
      return round(((($price_2 - $price_1) / $price_1) * 100), 3);
    } else {
      return round(((($price_1 - $price_2) / $price_2) * 100), 3);
    }
  } else {
    return "<span class='nan'></span>";
  }
}

function calculate_min($price_array) {
  if (is_array($price_array)) {
    return number_format((float)round(min($price_array), 3), 3, '.', '');
  } else {
    return "<span class='nan'></span>";
  }
}

function calculate_max($price_array) {
  if (is_array($price_array)) {
    return number_format((float)round(max($price_array), 3), 3, '.', '');
  } else {
    return "<span class='nan'></span>";
  }
}

function calculate_profit($cost, $price) {
  if ($price != 0) {
    return round(((($price - $cost) / $price) * 100), 3);
  }
}

// Generic Functions
function adjust_for_variance($price_array, $variance_threshold) {
  $variance = calculate_variance($price_array);
  if ($variance > $variance_threshold) {
    sort($price_array);
    array_pop($price_array);
    $price_array = adjust_for_variance($price_array, $variance_threshold);
  } else {
    return $price_array;
  }
}

function sanitize($var) {
  $var = preg_replace('~[^-a-zA-Z0-9_/,]~', '', $var); 
  return $var;
}

function format_number($number, $type = 'amount') {
  global $threshold;
  if (empty($number)) {
    return "<span class='nan'></span>";
  } elseif ($number != "<span class='nan'></span>") {
    
    // trim off 3rd zero at after the decimal
    $num = explode(".",$number);
    if (isset($num[1])) {
      if (strlen($num[1]) == 3 && substr($num[1], -1) == 0) {
        $num[1] = substr($num[1], 0, -1);
        $number = $num[0] . "." . $num[1]; 
      }
    }

    if ($type == 'amount') {
      return "$" . $number;
    } else {
      if ($number >= $threshold['high']) {
        $class = 'high';
      } elseif ($number <= $threshold['negative']) {
        $class = 'negative';
      } elseif ($number <= $threshold['very_low']) {
        $class = 'very-low';
      } elseif ($number <= $threshold['low']) {
        $class = 'low';
      } else {
        $class = 'standard';
      }

      return "<span class='" . $class . "'>" . $number . "%</span>";
    }
  } else {
    return $number;
  }
}

function system_message($text, $type = 'warning') {
  return "<div class='message " . $type . "'>" . $text . "</div>";
}

function check_login($logged_in) {
  $authorized = 0;

  if (isset($logged_in)) {
    if ($logged_in == 1) {
      $authorized = 1;
    }
  }

  return $authorized;
}

function write_log($logname, $contents) {
  file_put_contents($logname, $contents, FILE_APPEND);

  if (!is_readable($logname)) {
    file_put_contents('logs/system.txt', "Error writing login record: " . $contents, LOCK_EX, FILE_APPEND);
  } 
}

// Filter/Admin Functions
function set_multi_filter($var, $field) {
  if (isset($var)) {
    $get_var = implode(",", $var);
    //$get_var = ltrim(sanitize($get_var), '0');
    $get_var = sanitize($get_var);
  
    if (!empty($get_var)) {
      return "AND `" . $field . "` IN (" . $get_var . ")";
    } else {
      return NULL;
    }
  }
}

function set_filter($filter, $column) {
  if (isset($_GET[$filter])) {
    $filter_value = set_multi_filter($_GET[$filter], $column);
  } else {
    $filter_value = "";
  }

  return $filter_value;
}

function set_prepared_filter($var) {
  if (isset($_GET[$var])) {
    $filter_value = $_GET[$var];
  } else {
    $filter_value = "";
  }
  if (isset($filter_value)) {
    $get_var = implode(",", $filter_value);
    $get_var = sanitize($get_var);
  
    if (!empty($get_var)) {
      return $get_var;
    } else {
      return NULL;
    }
  }
}

function output_multi_filter($name, $get_var, $has_desc = 0) {
  global $filter;

  // determine the has-desc class for hiding autocomplete-enabled selects
  if ($has_desc == 1) { $desc = 'has-desc'; } else { $desc = 'no-desc'; } 
    echo "<div class='filter-container " . $desc . " " . strtolower($name) . "'>";
    echo "<h4>" . $name . "</h4>";

  // autocomplete fields
  if ($has_desc == 1) { 
    echo "<input id='" . strtolower($name) . "' class='desc'>";
    echo "<input type='hidden' id='" . strtolower($name) . "-id' class='id'>";
    echo "<span class='add'>+</span>";
    echo "<ul class='options'></ul>";
  }

  if (!empty($filter) && is_array($filter)) {  
    echo "<select multiple='yes' name='" . strtolower($name) . "[]' class='" . strtolower($name) . "'>";

    // iterate the results to build select options
    // used by humans and autocomplete
    foreach ($filter[$name] as $option) {

      // If we have a desc-enabled var we need to explode it back out
      if ($has_desc == 1) {
        $filter_option = explode("|", $option); 
        $filter_id     = $filter_option[1];
        $filter_desc   = $filter_option[0];
        $selected = ""; 

        if (is_array($get_var)) {
          if (in_array($filter_id, $get_var)) { $selected = "selected='selected'"; } 
        }

        echo "<option value='" . $filter_id . "' " . $selected . ">" . $filter_desc . "</option>\n";
      } else {
        $selected = ""; 
        if (is_array($get_var)) {
          print_r($get_var);
          print_r($option);
          if (in_array($option, $get_var)) { $selected = "selected='selected'"; } 
        }
        echo "<option value='" . $option . "' " . $selected . ">" . $option . "</option>\n";
      }
    } 
  }
  echo "</select></div>";
} 

function output_multi_filter_js($name, $get_var) {
  global $filter;

  if (!empty($filter) && is_array($filter)) {  
    echo "var " . strtolower($name) . "_array = [\n";
    foreach ($filter[$name] as $option) {
      $filter_option = explode("|", $option); 
      $filter_id     = $filter_option[1];
      $filter_desc   = $filter_option[0];

      echo "{\n";
      echo "value: \"" . $filter_id . "\",\n"; 
      echo "label: \"" . str_replace('"',"'",$filter_desc) . "\"\n"; 
      echo "},\n";
    } 
    echo "];\n";
  }
} 
