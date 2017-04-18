<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Silla
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html


*/

function SliderTop()  {
	return '<script type="text/javascript" src="'.qa_path_to_root().'qa-plugin/blog-post/js/jquery-1.9.1.min.js"></script>
			<script type="text/javascript" src="'.qa_path_to_root().'qa-plugin/blog-post/js/jssor.core.js"></script>
			<script type="text/javascript" src="'.qa_path_to_root().'qa-plugin/blog-post/js/jssor.utils.js"></script>
			<script type="text/javascript" src="'.qa_path_to_root().'qa-plugin/blog-post/js/jssor.slider.js"></script>
			<script>
			jQuery(document).ready(function ($) {
            var options = {
                $AutoPlay: true,                                    
                $AutoPlayInterval: 4000,                            
                $PauseOnHover: 1,                                   

                $ArrowKeyNavigation: true,   			            
                $SlideDuration: 800,                                
                $MinDragOffsetToSlide: 20,                          
                //$SlideWidth: 600,                                 
                //$SlideHeight: 200,                                
                $SlideSpacing: 0, 					                
                $DisplayPieces: 1,                                  
                $ParkingPosition: 0,                                
                $UISearchMode: 1,                                   
                $PlayOrientation: 1,                                
                $DragOrientation: 1,                                

                $ArrowNavigatorOptions: {                       
                    $Class: $JssorArrowNavigator$,              
                    $ChanceToShow: 1,                               
                    $AutoCenter: 2,                                 
                    $Steps: 1                                       
                },

                $ThumbnailNavigatorOptions: {
                    $Class: $JssorThumbnailNavigator$,              
                    $ChanceToShow: 2,                               

                    $ActionMode: 1,                                
                    $AutoCenter: 0,                                 
                    $Lanes: 1,                                      
                    $SpacingX: 3,                                    
                    $SpacingY: 3,                                    
                    $DisplayPieces: 9,                              
                    $ParkingPosition: 260,                          
                    $Orientation: 1,                                
                    $DisableDrag: false                            
                }
            };

            var jssor_slider1 = new $JssorSlider$("slider1_container", options);

            
            function ScaleSlider() {
                var bodyWidth = document.body.clientWidth;
                if (bodyWidth)
                    jssor_slider1.$SetScaleWidth(Math.min(bodyWidth, 600));
                else
                    window.setTimeout(ScaleSlider, 30);
            }

            ScaleSlider();

            if (!navigator.userAgent.match(/(iPhone|iPod|iPad|BlackBerry|IEMobile)/)) {
                $(window).bind("resize", ScaleSlider);
            }

        });
    </script>
    <div class="slider" style="position: relative; border-radius:5px;overflow: hidden;margin: 10px; padding: 12px;">
        <div style="position: relative; left: 50%;border-radius:5px; width: 4000px; text-align: center; margin-left: -2500px;">
            <div id="slider1_container" style="position: relative; margin: 0 auto;
                top: 0px; left: 0px; width: 90%; border-radius:5px;">
                <!-- Loading Screen -->
                <div u="loading" style="position: absolute; top: 0px; left: 0px;">
                    <div style="filter: alpha(opacity=70); opacity: 0.7; position: absolute; display: block;
                        top: 0px; left: 0px; width: 100%; height: 100%;">
                    </div>
                    <div style="position: absolute; display: block; background: url(img/loading.gif) no-repeat center center;
                        top: 0px; left: 0px; width: 100%; height: 100%;">
                    </div>
                </div>
                <!-- Slides Container -->
                ';
             }      

function ContentSlider($title,$postid,$link,$author,$date,$type,$content)  {
	return '<div u="slides" style="cursor: move; position: absolute; left: 0px; top: 0px; margin:50px;
                    overflow: hidden;"><div>
			<div style="position: absolute; width: 50%; height: 50%; top: 10px; left: 10px;
					text-align: left; line-height: 1.8em; font-size: 12px;">
					<br />
					<span style="display: block; line-height: 1em; text-transform: uppercase; font-size: 35px;
					   ">'.substr(strip_tags($title),0,40).'</span> 
					<br />
					<br /> 
					<span style="display: block; line-height: 1.1em; font-size: 2.5em;">
						'.AuthorHandle($author). ' '.DateAndTime($date).' </span>
					<br />
					<span style="display: block; line-height: 1.1em; font-size: 1.5em;">
						'.substr(strip_tags($content),0,160).' '.$type.'</span>
					<br /> 
					<br />
					<a href="'.$link.'">
						<img src="'.qa_path_to_root().'qa-plugin/blog-post/images/find-out-more-bt.png" border="0" alt="auction slider" width="215"
							height="50" /></a>
				</div>
				<img src="'.qa_path_to_root().'qa-plugin/blog-post/images/s2.png" style="position: absolute; top: 23px; left: 480px; width: 500px; height: 300px;" />
				<img u="thumb" src="'.qa_path_to_root().'qa-plugin/blog-post/images/s2t.jpg" />
			</div>';
			}
	
  function SliderBottom()  {
	return      '</div>
               
                <span u="arrowleft" class="jssora07l" style="width: 50px; height: 50px; top: 123px;
                    left: 8px;"></span>
                
                <span u="arrowright" class="jssora07r" style="width: 50px; height: 50px; top: 123px;
                    right: 8px"></span>

                <div u="thumbnavigator" class="jssort04" style="position: absolute; width: 600px;
                    height: 60px; right: 0px; bottom: 0px;">
                    
                    <div u="slides" style="bottom: 25px; right: 30px;">
                        <div u="prototype" class="p" style="position: absolute; width: 62px; height: 32px; top: 0; left: 0;">
                            <div class="w">
                                <thumbnailtemplate style="width: 100%; height: 100%; border: none; position: absolute; top: 0; left: 0;"></thumbnailtemplate>
                            </div>
                            <div class="c" style="position: absolute; background-color: #000; top: 0; left: 0">
                            </div>
                        </div>
                    </div>
                </div>
                <a style="display: none" href="http://www.jssor.com">javascript</a>
            </div>
            <!-- Jssor Slider End -->
        </div>
    </div>';
}