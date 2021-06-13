<?php
/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
class Google_Map {

    function __construct($options=array()){
        global $cfg, $user1, $db, $where, $cache, $links;

        $protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']=="off") ? "http://" : "https://";

        $defaults = array (
                            'js' => "<script type=\"text/javascript\" src=\"".$protocol."maps.google.com/maps/api/js?sensor=false\"></script>"
                            ,'html'=>""
                            ,'x'=>51.493485
                            ,'y'=>-0.225831
                            ,'sx'=>51.492891
                            ,'sy'=>-0.2258
                            ,'default_zoom'=>15
                            ,'markers'=>array()
                            ,'paths'=>array()
                            ,'image'=>false
                            ,'panControl'=>true
                            ,'zoomControl'=>true
                            ,'mapTypeControl'=>true
                            ,'scaleControl'=>true
                            ,'streetViewControl'=>true
                            ,'streetViewHeading'=>-20
                            ,'streetViewPitch'=>15
                            ,'streetViewZoom'=>2
                            ,'overviewMapControl'=>true
                            ,'navigationControl'=>true
                            ,'disableDoubleClickZoom'=>true
                            ,'draggable'=>true
                            ,'keyboardShortcuts'=>true
                            ,'scrollwheel'=>true
                            ,'minZoom'=>2
                            ,'maxZoom'=>100
                            ,'show_streetview'=>true
                            ,'width'=>"480px"
                            ,'height'=>"360px"
                            );

                            foreach($defaults as $key => $value) {
                                $this->{$key} = (isset($options[$key])) ? $options[$key] : $defaults[$key];
                            }
    }

    function set_defaults($options = array()) {
        foreach($defaults as $key => $value) {
            $this->{$key} = (isset($options[$key])) ? $options[$key] : $defaults[$key];
        }
    }

    function set_map_center($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    function set_streetview_center($x, $y) {
        $this->sx = $x;
        $this->sy = $y;
    }

    function erase_markers() {
        $this->markers = array();
    }

    function add_marker($x, $y, $options = array()) {
        $defaults = array(
                        'title' =>"",
                        'image'=>"",
                        'info_text'=>""
                        );
                        foreach ($options as $key=>$value) $defaults[$key] = $value;
                        $id = uniqid();
                        $temp = "
        var marker_loc_{$id} = new google.maps.LatLng({$x},{$y});
        var marker_{$id} = new google.maps.Marker({
            position: marker_loc_{$id}
            ,map: map";
                        if (!empty($defaults['title'])) $temp .= "
            ,title:\"{$defaults['title']}\"";
                        if (!empty($defaults['image'])) $temp .= "
            ,icon: \"{$defaults['image']}\"";
                        $temp .= "
            ,zindex:3
        });";

                        if (!empty($defaults['info_text'])) {
                            $temp .= "
            var info_{$id}= new google.maps.InfoWindow({ content: '{$defaults['info_text']}' });
            google.maps.event.addListener(
                marker_{$id},
                'click',
                function() {
                    info_{$id}.open(map,marker_{$id});
                });    ";
                        }

                        $this->markers[] = $temp;
    }

    function add_image_marker($x, $y) {
        $this->markers[] = "$x, $y";
    }

    function erase_paths() {
        $this->paths = array();
    }

    function add_path($options = array()) {
        $defaults = array(
                            'colour' =>"0x0000ff",
                            'weight'=>"2",
                            'opacity'=>"1.0",
                            'points'=>array()
        );
        // points should be in the form array(array('x'=>"", 'y'=>""))
        foreach ($options as $key=>$value) $defaults[$key] = $value;

        if (!isset($defaults['points'])
        || $defaults['points'] == array()
        || !isset($defaults['points'][0])
        || $defaults['points'][0] == array()
        || !isset($defaults['points'][0]['x'])
        || empty($defaults['points'][0]['x'])
        || !isset($defaults['points'][0]['y'])
        || empty($defaults['points'][0]['y'])
        ) {
            echo "Cannot add path, no position set";
        } else {
            $this->paths[] = $defaults;
        }
    }

    function set_html() {
        if ($this->image) {
            $this->html = "<img src=\"";
            $this->html .= $protocol."maps.googleapis.com/maps/api/staticmap?center=".$this->x.",".$this->y."&zoom=".$this->default_zoom;
            $this->html .= "&size=".$this->width."x".$this->height."&maptype=roadmap";
            if (!empty($this->markers)) $this->html .= "&markers=".implode("|",$this->markers);
            if (!empty($this->paths)) {
                foreach ($this->paths as $path) {
                    $this->html .= "&path=color:".$path['colour']."|weight:".$path['weight']."|";
                    $points = array();
                    foreach ($path['points'] as $point) {
                        $points[] = $point['x'].', '.$point['y'];
                    }
                    $this->html .= implode("|",$points);
                }
            }
            $this->html .= "&sensor=false\" />";
        } else {
            $this->html = "<div id=\"map_canvas\" style=\"position: relative !important; float: left; width: ".$this->width."; height: ".$this->height.";border: 1px solid #cfcfcf; margin:20px 10px 0 0; \"></div>";
            if ($this->show_streetview) $this->html .= "<div id=\"sw_canvas\" style=\"position: relative !important;float: left; width: ".$this->width."; height: ".$this->height.";border: 1px solid #cfcfcf; margin:20px 10px 0 0; \"></div>";
        }
    }

    function return_js() {
        $js = "<script type=\"text/javascript\">

                var riskpoint = new google.maps.LatLng(".$this->x.",".$this->y.");";
        if ($this->show_streetview) $js .= "var riskpoint_view = new google.maps.LatLng(".$this->sx.",".$this->sy.");";

        $js .= "
                var map;

                function initialize() {
                      var myOptions = {mapTypeId: google.maps.MapTypeId.ROADMAP
                          ,zoom: ".$this->default_zoom."
                          ,panControl:".($this->panControl ? 'true' : 'false')."
                          ,zoomControl:".($this->zoomControl ? 'true' : 'false')."
                          ,mapTypeControl:".($this->mapTypeControl ? 'true' : 'false')."
                          ,scaleControl:".($this->scaleControl ? 'true' : 'false')."
                          ,streetViewControl:".($this->streetViewControl ? 'true' : 'false')."
                          ,overviewMapControl:".($this->overviewMapControl ? 'true' : 'false')."
                          ,navigationControl:".($this->navigationControl ? 'true' : 'false')."
                          ,disableDoubleClickZoom:".($this->disableDoubleClickZoom ? 'true' : 'false')."
                          ,draggable:".($this->draggable ? 'true' : 'false')."
                          ,keyboardShortcuts:".($this->keyboardShortcuts ? 'true' : 'false')."
                          ,scrollwheel:".($this->scrollwheel ? 'true' : 'false')."
                          ,minZoom:".$this->minZoom."
                          ,maxZoom:".$this->maxZoom."
                    };
                      map = new google.maps.Map(document.getElementById(\"map_canvas\"),myOptions);
                      map.setCenter(riskpoint);
                      ".implode("\r\n",$this->markers);

        if ($this->paths != array()) {
            foreach ($this->paths as $path) {
                $js .= 'var flightPlanCoordinates = [';
                $points = array();
                foreach ($path['points'] as $point) {
                    $points[] = 'new google.maps.LatLng('.$point['x'].', '.$point['y'].')';
                }
                $js .= implode(", ", $points);
                $js .=    '    ];
                          var flightPath = new google.maps.Polyline({
                            path: flightPlanCoordinates,
                            strokeColor: "'.$path['colour'].'",
                            strokeOpacity: '.$path['opacity'].',
                            strokeWeight: '.$path['weight'].'
                          });

                          flightPath.setMap(map);';
            }
        }

        if ($this->show_streetview) $js .= "

                    var panoramaOptions = {position: riskpoint_view,pov: {heading: {$this->streetViewHeading},pitch: {$this->streetViewPitch},zoom: {$this->streetViewZoom}}};
                    var panorama = new  google.maps.StreetViewPanorama(document.getElementById(\"sw_canvas\"), panoramaOptions);
                    map.setStreetView(panorama);";

        $js .= "
                  }


                $(function() {initialize();})
                </script>\n";

        return $this->js . $js;
    }

    function return_html() {
        $this->set_html();
        return $this->html;
    }
}
