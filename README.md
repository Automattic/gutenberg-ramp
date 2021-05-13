# Gutenberg Ramp


### Deprecation notice

Please note that this plugin is deprecated. The WordPress core filters should be used instead. See [here](https://developer.wordpress.org/reference/hooks/use_block_editor_for_post/).

### Overview

Gutenberg Ramp is a plugin that manages the state of Gutenberg in the post-edit context.  It loads or unloads Gutenberg in post-edit according to criteria specified in theme code.  It is agnostic about whether Gutenberg is loading from core or via the plugin.

### How it Works

Gutenberg Ramp assumes one of the following states:

- WordPress 4.9 and the Gutenberg plugin (either activated or not)
- WordPress 5.0 and a fallback editor 

Gutenberg Ramp makes a decision early in the WordPress load sequence (`plugins_loaded`) about whether to take action.  It will take action if the following are true:

- either the post edit or new post screens are going to load AND
- according its user-supplied criteria either: Gutenberg should load for the current post and will not OR Gutenberg shouldn't load for the current post and will.

Loading criteria are supplied either in code (in a theme or plugin) or via UI. Gutenberg can be instructed to always or never load, or to load for just particular post_id or post_types.

### Specifying Loading Criteria

Criteria are stored in an option and specified by calling a function any time after `plugins_loaded`, typically in theme code or on a hook such as `init`.

Loading behavior is controlled by the `gutenberg_ramp_load_gutenberg()` function.  Calling this function without its single optional parameter causes Gutenberg to load on all post-edit screens.  An optional associative array of criteria can be passed.  The possible keys and values are:

- `load` (Int): `0|1`:  never or always load Gutenberg
- `post_ids` (Array of post_ids): loads Gutenberg for the specified post_ids
-  `post_types` (Array of post_types): loads Gutenberg for the specified post types.

### Code Examples

Load Gutenberg for all posts:
```php
if ( function_exists( 'gutenberg_ramp_load_gutenberg' ) ) {
	gutenberg_ramp_load_gutenberg();
}
```


Never load Gutenberg:
```php
gutenberg_ramp_load_gutenberg( false );

// Alternatively, you can use the `load` key to always disable Gutenberg:
gutenberg_ramp_load_gutenberg( [ 'load' => 0 ] );
```

Load Gutenberg only for posts with ids 12, 13 and 122:
```php
gutenberg_ramp_load_gutenberg( [ 'post_ids' => [ 12, 13, 122 ] ] );
```


Load Gutenberg for `post_id: 12` and all posts of type `test` and `scratch`:

```php
gutenberg_ramp_load_gutenberg(
	[
		'post_types' => [ 'test', 'scratch' ],
		'post_ids'   => [ 12 ],
	]
);
```


To load Gutenberg based on more specific conditions and advanced logic (for example only for a specific page template) call `gutenberg_ramp_load_gutenberg()` in the init hook instead of calling it directly, and use the post ID from the page request's `post` parameter to call whatever WordPress functions you need, like `get_page_template_slug`:
```php
function setGutenbergRampSetting() {
  if (isset($_REQUEST["post"])) {
    $pageTemplate = get_page_template_slug($_REQUEST["post"]);
    $useGutenberg = $pageTemplate === 'page-gutenberg.php';
    // other conditions and logic could go here, then call the function with the result
    gutenberg_ramp_load_gutenberg($useGutenberg);
  }
}
add_action('init', 'setGutenbergRampSetting');
```

### UI

Gutenberg Ramp adds a section to the Settings -> Writing menu that allows post_type control of Gutenberg loading.  This can be used in place of specifying criteria in code.


### FAQs

**Why is a post type disabled (greyed out) at Settings > Writing?**

If you're seeing something greyed out, it means the `gutenberg_ramp_load_gutenberg()` function is already in your theme functions.php. If you want to use the wp-admin UI, remove the conflicting function from your functions.php file.

**Why are some post types are not showing up on the settings screen?**

Post types that are not compatible with Gutenberg will not show up. If you think you have found a false negative (posts in that post type DO work with Gutenberg, when Ramp plugin is deactivated) please report it as an issue on [GitHub here.](https://github.com/Automattic/ramp-for-gutenberg)

**Can I contribute to this plugin?**

Absolutely! Please create issues and pull requests on [GitHub here.](https://github.com/Automattic/gutenberg-ramp)
