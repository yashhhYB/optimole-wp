<?php
/**
 * WordPress unit test plugin.
 *
 * @package     Optimole-WP
 * @subpackage  Tests
 * @copyright   Copyright (c) 2017, ThemeIsle
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Generic.
 */
class Test_Lazyload extends WP_UnitTestCase {
	const HTML_TAGS_HEADER = 'Test sample <header id="header"><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header2.jpg" width="2000" height="1200" alt="Test" /></div></div> </header><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header.jpg" width="2000" height="1200" alt="Test" /></div></div>';
	const HTML_TAGS_HEADER_MULTIPLE = 'Test sample <header id="header"><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header2.jpg" width="2000" height="1200" alt="Test" /></div></div> </header><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header.jpg" width="2000" height="1200" alt="Test" /></div></div>Test sample <header id="header"><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header3.jpg" width="2000" height="1200" alt="Test" /></div></div> </header><div id="wp-custom-header" class="wp-custom-header"><img src="http://example.org/wp-content/themes/twentyseventeen/assets/images/header4.jpg" width="2000" height="1200" alt="Test" /></div></div>';
	public static $sample_attachement;

	public function setUp() {
		parent::setUp();
		$settings = new Optml_Settings();
		$settings->update( 'service_data', [
			'cdn_key'    => 'test123',
			'cdn_secret' => '12345',
			'whitelist'  => [ 'example.com' ],

		] );
		$settings->update( 'lazyload', 'enabled' );
		Optml_Url_Replacer::instance()->init();
		Optml_Tag_Replacer::instance()->init();
		Optml_Lazyload_Replacer::instance()->init();
		Optml_Manager::instance()->init();

		self::$sample_attachement = self::factory()->attachment->create_upload_object( OPTML_PATH . 'assets/img/logo.png' );
	}

	public function test_lazy_load() {

		$replaced_content = Optml_Manager::instance()->process_images_from_content( Test_Replacer::IMG_TAGS );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertContains( '/http://', $replaced_content );

		$replaced_content = Optml_Manager::instance()->process_images_from_content( Test_Replacer::IMG_TAGS . Test_Replacer::IMG_URLS );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertContains( 'http://example.org', $replaced_content ); // Does not touch other URL's
		$this->assertContains( '/http://', $replaced_content );

		$replaced_content = Optml_Manager::instance()->process_images_from_content( Test_Replacer::IMG_TAGS_PNG );

		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertEquals( 1, substr_count( $replaced_content, 'data-opt-src' ) );

	}

	public function test_lazyload_json_data() {

		$some_html_content = [
			'html' => '<a href="http://example.org/blog/how-to-monetize-a-blog/"><img class="alignnone wp-image-36442 size-full" src="http://example.org/wp-content/uploads/2018/06/start-a-blog-1-5.png" alt="How to monetize a blog" width="490" height="256"></a> http://example.org/wp-content/uploads/2018/06/start-a-blog-1-5.png '
		];

		$content           = wp_json_encode( $some_html_content );
		$replaced_content  = Optml_Manager::instance()->replace_content( $content );
		$replaced_content2 = Optml_Manager::instance()->replace_content( $replaced_content );

		$this->assertEquals( $replaced_content, $replaced_content2 );
		$this->assertArrayHasKey( 'html', json_decode( $replaced_content2, true ) );

		$this->assertEquals( 4, substr_count( $replaced_content2, '/http:\/\/' ) );
	}


	public function test_replacement_with_jetpack_photon() {
		$content = '<div class="before-footer">
				<div class="codeinwp-container">
					<p class="featuredon">Featured On</p>
					<img src="http://i0.wp.com/www.example.org/wp-content/uploads/2018/05/brands.png"> http://i0.wp.com/www.example.org/wp-content/uploads/2018/05/brands.png
				</div>
			</div>';

		$replaced_content = Optml_Manager::instance()->replace_content( $content );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertNotContains( 'i0.wp.com', $replaced_content );
	}

	public function test_lazy_dont_lazy_load_headers() {
		$replaced_content = Optml_Manager::instance()->process_images_from_content( self::HTML_TAGS_HEADER );

		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'http://example.org', $replaced_content );
		$this->assertEquals( 1, substr_count( $replaced_content, 'data-opt-src' ) );
	}

	public function test_lazy_load_just_first_header() {
		$replaced_content = Optml_Manager::instance()->process_images_from_content( self::HTML_TAGS_HEADER_MULTIPLE );

		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'http://example.org', $replaced_content );
		$this->assertEquals( 3, substr_count( $replaced_content, 'data-opt-src' ) );
	}

	public function test_lazy_load_preserve_image_size() {
		$html             = wp_get_attachment_image( self::$sample_attachement, 'sample_size_crop' );
		$replaced_content = Optml_Manager::instance()->replace_content( $html );
		$this->assertNotEquals( $replaced_content, $html );
		$this->assertContains( 'rt:fill/g:ce', $replaced_content );

	}

	public function test_check_no_script() {
		$replaced_content = Optml_Manager::instance()->process_images_from_content( self::HTML_TAGS_HEADER );

		$this->assertContains( '<noscript>', $replaced_content );
		$this->assertEquals( 1, substr_count( $replaced_content, '<noscript>' ) );
	}

	public function test_lazy_load_ignore_feed() {
		$this->go_to( '/?feed=rss2' );

		$replaced_content = Optml_Manager::instance()->process_images_from_content( Test_Replacer::IMG_TAGS_WITH_SRCSET );
		$this->assertNotContains( 'i.optimole.com', $replaced_content );
		$this->assertNotContains( 'data-opt-src', $replaced_content );
	}

	public function test_lazy_load_off() {

		define( 'OPTML_DISABLE_PNG_LAZYLOAD', true );

		$replaced_content = Optml_Manager::instance()->process_images_from_content( Test_Replacer::IMG_TAGS_PNG . Test_Replacer::IMG_TAGS );
		$this->assertContains( 'i.optimole.com', $replaced_content );
		$this->assertContains( 'data-opt-src', $replaced_content );
		$this->assertEquals( 1, substr_count( $replaced_content, 'data-opt-src' ) );

	}

	public function test_lazyload_json_data_disabled() {

		$some_html_content = [
			'html' => '<a href="http://example.org/blog/how-to-monetize-a-blog/"><img class="alignnone wp-image-36442 size-full" src="http://example.org/wp-content/uploads/2018/06/start-a-blog-1-5.png" alt="How to monetize a blog" width="490" height="256"></a> http://example.org/wp-content/uploads/2018/06/start-a-blog-1-5.png '
		];

		$content           = wp_json_encode( $some_html_content );
		$replaced_content  = Optml_Manager::instance()->replace_content( $content );
		$replaced_content2 = Optml_Manager::instance()->replace_content( $replaced_content );

		$this->assertEquals( $replaced_content, $replaced_content2 );
		$this->assertArrayHasKey( 'html', json_decode( $replaced_content2, true ) );

		$this->assertEquals( 2, substr_count( $replaced_content2, '/http:\/\/example.org' ) );
	}

}