/**
 * jQuery.changeimg
 * @version  1.0.0
 * @author   Makoto Kida
 * @package AffiliateSystem Pro 2
 * @copyright Copyright 2010 WebSquare
 * 
 * Explanation
 * for affiliate system pro 2
 * change the input button image
**/
$(document).ready(function(){
  /* When the mouse is on the input_base module then the button will be changed. */
  $('.input_base').mouseover(function(){
    $(this).css("background","url(./img/splite.gif) no-repeat 0px -330px");
  });
  /* When the mouse moves from the input_base module then the button will be restored. */  
  $('.input_base').mouseout(function(){
    $(this).css("background","url(./img/splite.gif) no-repeat 0px -300px");
  });
});
