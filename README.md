# HameThread

Contributors: hametuha, Takahashi_Fumiki  
Tags: forum, support, thread, faq, woocommerce  
Tested up to: 7.0  
Stable tag: 2.1.0  
License: GPLv3 or later  
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Lightweight Q&A forum and support desk for WordPress: threads, best answers, private threads, and per-product support for WooCommerce.

## Description

HameThread turns WordPress into a thread-based **Q&A forum / support desk** without a heavyweight setup. Each topic is a `thread` post and replies are ordinary WordPress comments, so everything you already know about moderation, roles and the REST API keeps working.

It shines where a generic forum does not:

- **WooCommerce product support** — show a "Get support" button on a product and collect purchaser questions as threads, right inside _My Account_.
- **Private threads** — let users open threads only the author, invited people and editors can read. Perfect for paid or sensitive support.
- **Best answer** — mark the reply that resolved the question and flag the whole thread as _Resolved_.
- **Block-first** — drop the **Thread Button** block on any page to let visitors start a thread. No template editing, no code.

### Features

- `thread` post type with a `topic` taxonomy
- Comment-based replies with AJAX/REST posting (no full page reload)
- Best answer & resolved / unresolved status
- Private threads with capability checks
- Auto-close threads after a period of inactivity
- Upvotes on comments
- E-mail notifications to thread subscribers
- JSON-LD (`QAPage`) structured data for SEO
- WooCommerce _My Account_ support page and per-product support button
- Optionally enable comment threads on any public post type
- Admin settings under **Settings → Discussion** (no code required for common tweaks)

### Customization

Common settings are available under **Settings → Discussion**:

- Thread description
- Allow users to start private threads
- Post types that get HameThread-style comment threads

Everything else is filterable. See **For developers** below.

## Installation

1. Upload the plugin to `wp-content/plugins/hamethread` (or install from the Plugins screen) and activate it.
2. Visit **Settings → Discussion** to choose the post types and options you need.
3. Add the **Thread Button** block to any page or post to let visitors start a thread, or call `hamethread_button()` from a template.
4. Threads are listed at the `thread` archive (e.g. `/thread/`).

WooCommerce is optional. When it is active, a **Support** tab is added to _My Account_ and a support button can be shown on product pages.

## Frequently Asked Questions

### How do I let visitors start a thread?

Add the **Thread Button** block to a page, or call `hamethread_button( $parent_id, $label )` in a template. The button opens a thread form powered by the REST API.

### Can I use it on posts or custom post types, not just threads?

Yes. Go to **Settings → Discussion → Thread Comments** and tick the post types you want, or use the `hamethread_dynamic_comment_post_types` filter.

### Does it require WooCommerce?

No. WooCommerce integration is enabled automatically only when WooCommerce is active; otherwise the plugin works as a standalone forum.

### How do I customize the form, validation or notifications?

HameThread exposes a large set of filters and actions. See **For developers**.

## For developers

Replies are standard WordPress comments and threads are a standard post type, so core hooks apply. On top of that, HameThread exposes many filters/actions. The most useful ones:

| Hook                                                                       | Type   | Purpose                                           |
| -------------------------------------------------------------------------- | ------ | ------------------------------------------------- |
| `hamethread_before_thread_form` / `hamethread_after_thread_form`           | action | Add markup/fields to the thread form              |
| `hamethread_new_thread_post_params`                                        | filter | Register extra REST parameters for new threads    |
| `hamethread_new_thread_post_arg`                                           | filter | Modify the post array before a thread is inserted |
| `hamethread_new_thread_validation`                                         | filter | Add validation errors (`WP_Error`)                |
| `hamethread_new_thread_inserted`                                           | action | React after a thread is created                   |
| `hamethread_before_comment_form` / `hamethread_after_comment_form`         | action | Add markup/fields to the comment form             |
| `hamethread_new_comment_params`                                            | filter | Modify comment data before insert                 |
| `hamethread_default_subscribers` / `hamethread_subscribers`                | filter | Control who is notified                           |
| `hamethread_user_can_start_private_thread`                                 | filter | Allow private threads                             |
| `hamethread_user_can_comment` / `hamethread_user_can_post`                 | filter | Capability checks                                 |
| `hamethread_dynamic_comment_post_types`                                    | filter | Post types that get comment threads               |
| `hamethread_post_setting` / `hamethread_post_type` / `hamethread_taxonomy` | filter | Customize the post type & taxonomy                |
| `hamethread_template`                                                      | filter | Override template parts                           |

**Example — add a field to the thread form and store it:**

```php
// 1. Render the field.
add_action( 'hamethread_after_thread_form', function ( $args, $default ) {
	if ( $args['post'] ) {
		return; // Only on new threads.
	}
	echo '<label><input type="checkbox" name="notify_staff" value="1" /> Notify staff</label>';
}, 10, 2 );

// 2. Register the REST parameter.
add_filter( 'hamethread_new_thread_post_params', function ( $params ) {
	$params['notify_staff'] = [ 'type' => 'integer', 'default' => 0 ];
	return $params;
} );

// 3. React after the thread is created.
add_action( 'hamethread_new_thread_inserted', function ( $post_id, $request ) {
	if ( $request->get_param( 'notify_staff' ) ) {
		// notify…
	}
}, 10, 2 );
```

**Example — enable comment threads on the `post` post type:**

```php
add_filter( 'hamethread_dynamic_comment_post_types', function ( $post_types ) {
	$post_types[] = 'post';
	return $post_types;
} );
```

A full list of hooks lives in the source. See the `app/` directory and [the GitHub repository](https://github.com/hametuha/hamethread).

## Changelog

[![Hamethread Test](https://github.com/hametuha/hamethread/actions/workflows/test.yml/badge.svg)](https://github.com/hametuha/hamethread/actions/workflows/test.yml)

### 2.1.0

- Add the **Thread Button** block for the block editor.
- Add a settings screen (**Settings → Discussion**) for thread description, private threads and comment-enabled post types.
- Improve escaping, i18n and coding standards for WordPress.org Plugin Check compliance.

### 2.0.0

- Removed dependencies on Twitter Bootstrap and FontAwesome.
- Modernize JavaScripts.

### 1.2.0

- Bump minimum PHP version to 7.4.

### 1.1.0

- Add structured data.
- Add best answer feature.

### 1.0.0

- Initial release.

---

See our all changelog on [GitHub Releases](https://github.com/hametuha/hamethread/releases).
