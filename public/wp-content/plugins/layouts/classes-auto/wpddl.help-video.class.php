<?php
class WPDDL_HelpVideos extends Toolset_HelpVideosFactoryAbstract{

    protected function define_toolset_videos(){
        return  array(
            'layouts_template' =>  array(
                'name' => 'layouts_template',
                'url' => 'https://www.youtube.com/watch?v=rbIcIT8G3FA&yt',
                'screens' => array('toolset_page_dd_layouts_edit'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Render Views Content Templates with Layouts', 'ddl-layouts'),
                'width' => '820px',
                'height' => '470px',
	            'renderer' => 'YouTube',
	            'track' => 'http://video.google.com/timedtext?lang=en&v=rbIcIT8G3FA&yt'
            ),
            'archive_layout' =>  array(
                'name' => 'archive_layout',
                'url' => 'https://www.youtube.com/watch?v=8s6NISaTF8g&yt',
                'screens' => array('toolset_page_dd_layouts_edit'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Render Views WordPress Archives with Layouts', 'ddl-layouts'),
                'width' => '820px',
                'height' => '470px',
                'renderer' => 'YouTube',
                'track' => 'http://video.google.com/timedtext?lang=en&v=8s6NISaTF8g&yt'
            ),
            'content_layout' =>  array(
	            'name' => 'content_layout',
	            'url' => $this->get_content_layout_video_url(),
	            'screens' => array('toolset_page_dd_layouts_edit'),
	            'element' => '.toolset-video-box-private-wrap',
	            'title' => __('Design single pages and posts using Layouts plugin', 'ddl-layouts'),
	            'width' => '820px',
	            'height' => '470px',
	            'renderer' => 'YouTube',
	            'track' => 'http://video.google.com/timedtext?lang=en&v=65QIMC93-pg&yt'
            ),
        );
    }
    private function get_content_layout_video_url(){

	    if( apply_filters( 'ddl-is_integrated_theme', false ) ){
		    return 'https://www.youtube.com/watch?v=65QIMC93-pg&yt';
	    } else {
		    return 'https://www.youtube.com/watch?v=65QIMC93-pg&yt';
	    }

    }
}
