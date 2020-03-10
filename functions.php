<?php

define ( 'THEME_WP_SHOW', true );
define ( 'THEME_INC', 1 ); /* ++++++ */
 
if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
	});

	add_filter('template_include', function( $template ) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});

	return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'templates' );


/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = true;


/**
 * Enable cache
 */
Timber::$cache = defined('TIMBER_CACHE') ? TIMBER_CACHE : false;


/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class StarterSite extends Timber\Site 
{
	/**
	 * Context
	 */
	public $robots;
	public $main_menu;
	public $categories;
	public $language_code;
	public $routes;
	public $route_info = null;
	public $f_inc = "";
	public $full_title = "";
	public $search_text = "";
	public $page_vars = [];
	public $page = 0;
	public $pages = 0;
	public $breadcrumbs = null;
	public $og_type = "";
	public $article_tags = [];
	public $article_section = "";
	public $article_published_time = "";
	public $article_modified_time = "";
	public $canonical_url = "";
	public $prev_url = "";
	public $next_url = "";
	public $post = null;
	public $post_id = "";
	public $post_category = null;
	
	
	/** Constructor **/
	
	public function __construct() 
	{
		parent::__construct();
		
		// Register hooks
		$this->register_hooks();
		
		// Register routes
		$this->register_routes();
	}
	
	
	public function setup()
	{
		/* Setup base variables */
		$this->f_inc = THEME_INC;
		$this->search_text = isset($_GET['s']) ? $_GET['s'] : "";
		$this->categories = get_categories();
		$this->post = get_queried_object();
		if ($this->post != null)
		{
			$this->post_id = ($this->post != null) ? $this->post->ID : "";
			if ($this->post instanceof WP_POST) $this->post_category = get_the_category($this->post_id);
		}
		$this->page_vars = 
		[
			"wp_show" => THEME_WP_SHOW,
			"is_admin" => current_user_can('administrator'),
			"is_archive" => is_archive(),
			"is_category" => is_category(),
			"is_page" => is_page(),
			"is_home" => is_home(),
			"is_front_page" => is_front_page(),
			"is_single" => is_single(),
			"is_singular" => is_singular(),
			"is_post" => $this->post instanceof WP_POST,
		];
		$this->language = get_locale();
		$this->language_code = $this->get_current_locale_code();
		$this->title = $this->get_page_title();
		$this->full_title = $this->get_page_full_title($this->title);
		$this->description = $this->get_page_description();
		$this->robots = $this->get_page_robots();
		$this->name = $this->get_site_name();
		$this->page = max( 1, (int) get_query_var( 'paged' ) );
		$this->pages = $GLOBALS['wp_query']->max_num_pages;
		
		/* Setup prev and next url */
		$this->setup_links();
		
		/* Setup article tags */
		$this->setup_article_tags();
		
		/* Setup breadcrumbs */
		$this->setup_breadcrumbs();
		
		/* Setup menu */
		/* $this->main_menu = new Timber\Menu('main-' . $this->language_code); */
	}
	
	
	
	/** Functions **/
	
	function get_canonical_url($un_paged = false)
	{
		global $wp;
		
		$site_url = $this->site_url;
		$locale_code = $this->get_current_locale_code();
		
		$uri = $wp->request;
		if (strpos($uri, $locale_code) === 0)
		{
			$uri = substr($uri, strlen($locale_code) + 1);
		}
		
		if ($un_paged)
		{
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			$str = "page/" . $paged;
			if (strpos($uri, $str) !== false)
			{
				$uri = substr($uri, 0, -strlen($str));
			}
		}
		
		$url = $this->url_concat($site_url . "/" . $locale_code, $uri);
		if (substr($url, -1) == "/") $url = substr($url, 0, -1);
		if ($uri == false) $url .= "/";
		
		return $url;
	}
	
	public function get_site_name()
	{
		return get_bloginfo("name");
	}
	
	public function get_site_description()
	{
		return get_bloginfo("description");
	}
	
	public function get_page_title()
	{
		$route_params = $this->get_route_params();
		if ($route_params != null)
		{
			return $route_params['title'];
		}
		
		if (is_home())
		{
			$title = $this->get_site_name();
		}
		else
		{
			$vars = $this->page_vars;
			$title = "";
			if ($this->post != null && $this->post->taxonomy == 'category')
			{
				$title = "Категория " . $this->post->name;
			}
			else if ($this->post != null && $this->post->taxonomy == 'post_tag')
			{
				$title = "Тег " . $this->post->name;
			}
			else if (is_archive())
			{
				$title = "Архив за " . $this->get_the_archive_title();
			}
			else if ($this->search_text != null)
			{
				$title = "Результаты поиска для " . $this->search_text;
			}
			else
			{
				$title = get_the_title();
			}
		}
		
		$page = max( 1, (int) get_query_var( 'paged' ) );
		if ($page > 1)
		{
			$title .= " страница " . $page;
		}
		
		return $title;
	}
	
	public function get_page_full_title($title)
	{
		if (is_home())
		{
			return $title;
		}
		return $title . " | " . $this->name;
	}
	
	public function get_page_description()
	{
		$route_params = $this->get_route_params();
		if ($route_params != null)
		{
			return $route_params['description'];
		}
		
		$str = \RankMath\Paper\Paper::get()->get_description();
		if ($str == "")
		{
			$str = get_bloginfo("description");
		}
		
		return $str;
	}
	
	public function get_page_robots()
	{
		if ( class_exists(\RankMath\Paper\Paper::class) )
		{
			$robots = \RankMath\Paper\Paper::get()->get_robots();
			if (!isset($robots['index'])) $robots['index'] = 'index';
			if (!isset($robots['follow'])) $robots['follow'] = 'follow';
			if (isset($robots['max-snippet'])) unset($robots['max-snippet']);
			if (isset($robots['max-video-preview'])) unset($robots['max-video-preview']);
			if (isset($robots['max-image-preview'])) unset($robots['max-image-preview']);
			return implode( ",", array_values($robots) );
		}
		return "";
	}
	
	function get_current_locale_code()
	{
		$locale = get_locale();
		if ($locale == "ru_RU") return "ru";
		else if ($locale == "en_US") return "en";
		return "";
	}
	
	public function setup_breadcrumbs()
	{
		if ( class_exists(\RankMath\Frontend\Breadcrumbs::class) )
		{
			$breadcrumbs = \RankMath\Frontend\Breadcrumbs::get();
			if ($breadcrumbs)
			{
				$canonical_url =$this->canonical_url;
				$site_url = $this->site_url;
				$site_url_sz = strlen($site_url);
				$data = $breadcrumbs->get_crumbs();
				$data = array_map
				(
					function ($item) use ($site_url_sz, $canonical_url)
					{
						if ($item[1] == "") $item[1] = $canonical_url;
						$item[1] = substr($item[1], $site_url_sz);
						return $item;
					},
					$data
				);
				
				$data = array_filter
				(
					$data,
					function ($item)
					{
						if ($item[0] == "") return false;
						return true;
					}
				);
				
				$data[0][1] = "/" . $this->language_code() . "/";
				if (count($data) > 1) $data[ count($data) - 1 ][0] = $this->title;
				
				$this->breadcrumbs = $data;
			}
		}
	}
	
	public function setup_links()
	{
		$this->canonical_url = $this->get_canonical_url();
		
		if (!is_singular())
		{
			$canonical_url = $this->get_canonical_url(true);
			$paged = max( 1, (int) get_query_var( 'paged' ) );
			$max_page = $GLOBALS['wp_query']->max_num_pages;
			
			$prev_url = ($paged <= 2) ? $canonical_url : $this->url_concat($canonical_url, "/page/" . ($paged - 1));
			$next_url = $this->url_concat($canonical_url, "/page/" . ($paged + 1));
			
			if ($paged >= 2 && $paged < $max_page)
			{
				$this->prev_url = $prev_url;
			}
			if ($paged < $max_page)
			{
				$this->next_url = $next_url;
			}
		}
	}
	
	public function setup_article_tags()
	{
		if ($this->post instanceof WP_POST)
		{
			$this->og_type = "article";
			
			$dt = new \DateTime($this->post->post_date_gmt, new \DateTimezone("UTC"));
			$dt->setTimezone( new \DateTimezone(date_default_timezone_get()) );
			$this->article_published_time = $dt->format("c");
			
			$dt = new \DateTime($this->post->post_modified_gmt, new \DateTimezone("UTC"));
			$dt->setTimezone( new \DateTimezone(date_default_timezone_get()) );
			$this->article_modified_time = $dt->format("c");
			
			/* Setup article section */
			if ($this->post_category and count($this->post_category) > 0)
			{
				$this->article_section = $this->post_category[0]->name;
			}
			
			/* Setup article tags */
			$tags = wp_get_post_tags($this->post_id);
			$this->article_tags = array_map( function ($item) { return $item->name; }, $tags );
		}
	}
	
	public function get_the_archive_title() 
	{
		if ( is_category() ) {
			/* translators: Category archive title. %s: Category name. */
			$title = sprintf( __( '%s' ), single_cat_title( '', false ) );
		} elseif ( is_tag() ) {
			/* translators: Tag archive title. %s: Tag name. */
			$title = sprintf( __( '%s' ), single_tag_title( '', false ) );
		} elseif ( is_author() ) {
			/* translators: Author archive title. %s: Author name. */
			$title = sprintf( __( '%s' ), '<span class="vcard">' . get_the_author() . '</span>' );
		} elseif ( is_year() ) {
			/* translators: Yearly archive title. %s: Year. */
			$title = sprintf( __( '%s' ), get_the_date( _x( 'Y', 'yearly archives date format' ) ) );
		} elseif ( is_month() ) {
			/* translators: Monthly archive title. %s: Month name and year. */
			$title = sprintf( __( '%s' ), get_the_date( _x( 'F Y', 'monthly archives date format' ) ) );
		} elseif ( is_day() ) {
			/* translators: Daily archive title. %s: Date. */
			$title = sprintf( __( '%s' ), get_the_date( _x( 'F j, Y', 'daily archives date format' ) ) );
		}
	 
		/**
		 * Filters the archive title.
		 */
		return apply_filters( 'get_the_archive_title', $title );
	}
	
	
	/** Actions & Filters **/
	
	function register_hooks()
	{
		add_action( 'after_setup_theme', array( $this, 'action_theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'action_add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'action_add_to_twig' ) );
		add_action( 'init', array( $this, 'action_register_post_types' ) );
		add_action( 'init', array( $this, 'action_register_taxonomies' ) );
		add_action( 'widgets_init', array( $this, 'action_widgets_init' ) );
		add_action( 'wp', array( $this, 'setup' ), 99999 );
		add_filter( 'term_link', function($url){ return str_replace('/./', '/', $url); }, 10, 1 );
		
		// Title
		add_filter( 'wp_title', [$this, 'filter_page_title'], 99999, 1 );
		add_filter( 'thematic_doctitle', [$this, 'filter_page_title'], 99999, 1 );
		add_filter( 'pre_get_document_title', [$this, 'filter_page_title'], 99999, 1 );
	}
	
	function filter_page_title($orig_title)
	{
		if (is_home())
		{
			return $this->title;
		}		
		return $this->title . " | " . $this->site_name;
	}
	
	
	
	/** Theme settings **/
	
	public function action_widgets_init()
	{
		register_sidebar(
			array(
				'name'          => 'Blog Sidebar',
				'id'            => 'sidebar-1',
				'description'   => 'Add widgets here to appear in your sidebar on blog posts and archive pages.',
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
	
	public function action_theme_supports() 
	{
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		// add_theme_support( 'title-tag' );

		/**
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		/**
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5', array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/**
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats', array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);
		
		add_theme_support( 'menus' );
	}
	
	/** This is where you can register custom post types. */
	public function action_register_post_types()
	{
		
	}
	
	/** This is where you can register custom taxonomies. */
	public function action_register_taxonomies()
	{

	}
	
	
	/** 
	 * This is where you add some context 
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function action_add_to_context( $context ) 
	{
		/* Setup context */
		$context['site'] = $this;
		return $context;
	}
	
	
	
	/** Routes **/
	
	/**
	 * Add route
	 */
	public function add_route($route_name, $match, $template = null, $params=[])
	{
		if ($template != null)
		{
			Routes::map(
				$match,
				function($matches) use ($route_name, $template, $params)
				{
					$this->route_name = $route_name;
					$this->route_matches = $matches;
					$this->route_template = $template;
					$this->route_params = $params;
					
					Routes::load
					(
						'route.php', 
						[
							"timber_site" => $this,
						], 
						"/",
						200
					);
				}
			);
		}
		$this->routes[$route_name] = 
		[
			'route_name' => $route_name,
			'template' => $template,
			'params' => $params,
			'match' => $match,
		];
	}
	
	function get_route_params()
	{
		if ($this->route_params == null) return null;
		if (!isset($this->route_params['params'])) return null;
		return $this->route_params['params'];
	}
	
	
	
	/** Twig functions **/
	
	/**
	 * This is where you can add your own functions to twig.
	 *
	 * @param string $twig get extension.
	 */
	public function action_add_to_twig( $twig )
	{
		$twig->addExtension( new Twig_Extension_StringLoader() );		
		$twig->registerUndefinedFunctionCallback(function ($name) {
			if (method_exists($this, $name))
			{
				return new \Twig_Function_Function( array( $this, $name ) );
			}
			if (!function_exists($name))
			{
				return false;
			}
			return new \Twig_Function_Function($name);
		});
		
		$twig->registerUndefinedFilterCallback(function ($name) {
			if (!function_exists($name))
				return false;
			return new \Twig_Function_Function($name);
		});
		
		$twig->addFunction( new Twig_SimpleFunction( 'count', array( $this, 'get_count' ) ) );
		$twig->addFunction( new Twig_SimpleFunction( 'dump', array( $this, 'var_dump' ) ) );
		$twig->addFunction( new Twig_SimpleFunction( 'url', array( $this, 'url_new' ) ) );
		
		return $twig;
	}
	
	function get_sub_categories($categories, $parent_id)
	{
		if (gettype($categories) != 'array') return [];
		return array_filter
		(
			$categories,
			function ($item) use ($parent_id)
			{
				if ($item->parent == $parent_id) return true;
				return false;
			}
		);
	}
	
	function url_concat($url, $add)
	{
		if (strlen($add) == 0) return $url;
		if ($add[0] == "/") $add = substr($add, 1);
		if (strlen($add) == 0) return $url;
		if (substr($url, -1) != "/") return $url . "/" . $add;
		return $url . $add;
	}
	
	function url_new($name, $params=null)
	{
		return isset($this->routes[$name]) ? $this->routes[$name]['match'] : '';
	}

	function isRouteNameBegins()
	{
		return false;
	}

	function isUrlsEquals()
	{
		return false;
	}
	
	function post_preview($content, $count = 50)
	{
		$f = preg_match('/<!--\s?more(.*?)?-->/', $content, $readmore_matches);
		if ($readmore_matches != null and isset($readmore_matches[0]))
		{
			$pieces = explode($readmore_matches[0], $content);
			if ($f)
			{
				$text = $pieces[0];
			}
			else
			{
				$text = $content;
			}
		}
		else
		{
			$text = $content;
		}
		$preview = $text;
		//$preview = str_replace("<p>", "", $preview);
		//$preview = str_replace("</p>", "<br/>", $preview);
		$preview = \Timber\TextHelper::trim_words($preview, $count, "", "a span b i br blockquote p");
		
		return $preview;
	}
	
	function get_count($x)
	{
		return count($x);
	}
	
	function var_dump($v)
	{
		echo "<pre>";
		var_dump($v);
		echo "</pre>";
		return "";
	}
}

global $timber_site;
if (!$timber_site)
{
	$timber_site = new StarterSite();
}