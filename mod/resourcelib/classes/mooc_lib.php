<?php
require_once($CFG->dirroot.'/lib/outputrenderers.php');
require_once($CFG->dirroot.'/rating/lib.php');

class mooc_renderer extends core_renderer {

    /**
     * Constructor
     *
     * @param moodle_page $page the page we are doing output for.
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        global $CFG;
        // execute parent constructor
        parent::__construct($page, $target);
        // output jquery.raty library
        if ($file_content = file_get_contents($CFG->dirroot.'/mod/resourcelib/raty-master/jquery.raty.js')) {
            echo   //wrap contant of js-file into <script> tag
            '<script type="text/javascript">
                //<![CDATA[
                ' . $file_content . '
                //]]>
            </script>';
        }
    }
    
    /**
     * Produces the html that represents this rating in the UI
     *
     * @param rating $rating the page object on which this rating will appear
     * @return string
     */
    public function render_rating(rating $rating) {
        global $CFG, $USER;

        if ($rating->settings->aggregationmethod == RATING_AGGREGATE_NONE) {
            return null;//ratings are turned off
        }

        /// Create instance of mooc_rating_manager
        $ratingmanager = new mooc_rating_manager();
        // ... and set custom Label for widget!
        $ratingmanager->customLabel = 'Rate this resource';
         
        // Initialise the JavaScript so ratings can be done by AJAX.
        $ratingmanager->initialise_rating_javascript($this->page);

        $strrate = 'Rate this resource'; //get_string("rate", "rating");
        $ratinghtml = ''; //the string we'll return

        // permissions check - can they view the aggregate?
        if ($rating->user_can_view_aggregate()) {

            $aggregatelabel = $ratingmanager->get_aggregate_label($rating->settings->aggregationmethod);
            $aggregatestr   = $rating->get_aggregate_string();

            $aggregatehtml  = html_writer::tag('span', $aggregatestr, array('id' => 'ratingaggregate'.$rating->itemid, 'class' => 'ratingaggregate')).' ';
            if ($rating->count > 0) {
                $countstr = "({$rating->count})";
            } else {
                $countstr = '-';
            }
            $aggregatehtml .= html_writer::tag('span', $countstr, array('id'=>"ratingcount{$rating->itemid}", 'class' => 'ratingcount')).' ';

            $ratinghtml .= html_writer::tag('span', $aggregatelabel, array('class'=>'rating-aggregate-label'));
            if ($rating->settings->permissions->viewall && $rating->settings->pluginpermissions->viewall) {

                $nonpopuplink = $rating->get_view_ratings_url();
                $popuplink = $rating->get_view_ratings_url(true);

                $action = new popup_action('click', $popuplink, 'ratings', array('height' => 400, 'width' => 600));
                $ratinghtml .= $this->action_link($nonpopuplink, $aggregatehtml, $action);
            } else {
                $ratinghtml .= $aggregatehtml;
            }
        }

        $formstart = null;
        // if the item doesn't belong to the current user, the user has permission to rate
        // and we're within the assessable period
        if ($rating->user_can_rate()) {

            $rateurl = $rating->get_rate_url();
            $inputs = $rateurl->params();

            //start the rating form
            $formattrs = array(
                'id'     => "postrating{$rating->itemid}",
                'class'  => 'postratingform',
                'method' => 'post',
                'action' => $rateurl->out_omit_querystring()
            );
            $formstart  = html_writer::start_tag('form', $formattrs);
            $formstart .= html_writer::start_tag('div', array('class' => 'ratingform'));

            // add the hidden inputs
            foreach ($inputs as $name => $value) {
                $attributes = array('type' => 'hidden', 'class' => 'ratinginput', 'name' => $name, 'value' => $value);
                $formstart .= html_writer::empty_tag('input', $attributes);
            }

            if (empty($ratinghtml)) {
                $ratinghtml .= $strrate . get_string('labelsep', 'langconfig');
            }
            $ratinghtml = $formstart.$ratinghtml;

            $scalearray = array(RATING_UNSET_RATING => $strrate.'...') + $rating->settings->scale->scaleitems;
            $scaleattrs = array('class'=>'postratingmenu ratinginput','id'=>'menurating'.$rating->itemid);
            // set invisibility for select-element
            $scaleattrs['style'] = 'display: none';
            $ratinghtml .= html_writer::label($rating->rating, 'menurating'.$rating->itemid, false, array('class' => 'accesshide'));
            $ratinghtml .= html_writer::select($scalearray, 'rating', $rating->rating, false, $scaleattrs);
            
            // -------- add Star Raty plugin
            $itemid = 'star_menurating'.$rating->itemid;
            $rating_value = isset($rating->rating) ? 'readOnly: true, score: '.$rating->rating : '';
            $callback_click = 'click: function(score, evt) {
                                  id = $(this).data("id");
                                  select = $("select#menurating" + id);
                                  select.children("[value=" + score + "]").attr("selected", "selected");
                                  sel = Y.one("select#menurating" + id);
                                  M.core_rating.submit_rating("change", sel);  //Y.fire("change", {nodes: sel}); 
                                  $(this).raty("readOnly", true);
                              }, ';
            $ratinghtml .= html_writer::start_span('star-rate', array('id'=>$itemid, 'data-id'=>$rating->itemid)) . 
                           html_writer::end_span(); // DOM-container for Star Raty Plugin
            $ratinghtml .= '<script type="text/javascript"> 
                                $("#'.$itemid.'").raty({' . $callback_click . $rating_value . ' }); 
                            </script>';   // set Raty plugin into container

            // Output submit button
            $ratinghtml .= html_writer::start_tag('span', array('class'=>"ratingsubmit"));

            $attributes = array('type' => 'submit', 'class' => 'postratingmenusubmit', 
                'id' => 'postratingsubmit'.$rating->itemid, 
                'value' => s(get_string('rate', 'rating')),
                'style' => 'display: none',    // set invisible mode for submit button
            );
            $ratinghtml .= html_writer::empty_tag('input', $attributes);

            if (!$rating->settings->scale->isnumeric) {
                // If a global scale, try to find current course ID from the context
                if (empty($rating->settings->scale->courseid) and $coursecontext = $rating->context->get_course_context(false)) {
                    $courseid = $coursecontext->instanceid;
                } else {
                    $courseid = $rating->settings->scale->courseid;
                }
                $ratinghtml .= $this->help_icon_scale($courseid, $rating->settings->scale);
            }
            $ratinghtml .= html_writer::end_tag('span');
            $ratinghtml .= html_writer::end_tag('div');
            $ratinghtml .= html_writer::end_tag('form');
        }

        return $ratinghtml;
    }
    
}  

class mooc_rating_manager extends rating_manager {

    // custom label fore rating widget
    public $customLabel = '';

    /**
     * Returns a string that describes the aggregation method that was provided.
     *
     * @param string $aggregationmethod
     * @return string describes the aggregation method that was provided
     */
    public function get_aggregate_label($aggregationmethod) {
        // firstly run parent method
        $aggregatelabel = parent::get_aggregate_label($aggregationmethod);
        // then check setting of custom Label
        if (!empty($this->customLabel)) {
            $aggregatelabel = $this->customLabel . get_string('labelsep', 'langconfig');
        }
        return $aggregatelabel;
    }
    
}
?>
