<?php

function nameid($id) {
  return ' name="' . $id . '" id="' . $id . '" ';
}

function select($id,$keys,$values,$selectedkey,$props=null){
  $html = '<select ' . nameid($id);
  if ( $props != null ) {
    foreach ($props as $key => $value) {
      $html .= ' ' . $key . "=\"" . $value . "\"";
    }
  }
  $html .= ">";
  for ($i=0;$i < sizeof($keys); $i++ ) {
    $html .= "\n<option value=\"" .$keys[$i] . "\" ";
    if ( isset($selectedkey) && $keys[$i] == $selectedkey ) {
      $html .= " selected=\"selected\" ";
    }
    $html.= ">". $values[$i] . "</option>";
  }
  $html .= "\n</select>";
  return $html;
}


function hidden($id,$value){
  return '<input type="hidden"' . nameid($id) . '" value="' . $value . '" />';
}

function p($text) {
  return "<p>" . $text  ."</p>";
}

function td($text){
  return "<td>" . $text  ."</td>";
}

function pre($text) {
  return "<pre>" . $text . "</pre>";
}

function span($text,$props) {
  return html('span',$text,$props);
}

function a($text,$props) {
  return html('a',$text,$props);
}

function html($element,$text,$props){
  $html = '<'.$element;
  if ( isset($props) ) {
    foreach ($props as $key => $value) {
      $html .= ' ' . $key . "=\"" . $value . "\"";
    }
  }
  $html .= '>' . $text . '</' . $element . '>';
  return $html;
}


?>