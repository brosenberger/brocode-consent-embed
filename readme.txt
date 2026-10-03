=== BroCode Consent Embed ===
Contributors: brosenberger
Tags: gdpr, privacy, consent, youtube, google maps
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Click-to-load embeds and consent sections: maps, videos, calendars or any other blocks load only after the visitor agrees.

== Description ==

Embedding a map or a video loads it straight from the provider, which hands every visitor's IP address to Google, Vimeo and others before anyone has agreed to it. Under the GDPR that needs consent first.

BroCode Consent Embed shows a placeholder instead. The iframe is created only after the visitor clicks **Load**. Until then, no request goes to the provider: no thumbnail, no script, no cookie.

= Features =

* **Consent Embed block** for Google Maps, Google Calendar, YouTube, Vimeo and OpenStreetMap. Paste a link or a whole `<iframe>` snippet.
* **Consent Section block for everything else.** Wrap any blocks in it, such as a Custom HTML widget, a booking form, a social feed or a group of embeds, and name the service. The blocks stay inert until the visitor consents: no image, iframe or script inside loads before that.
* **Existing embeds are covered too.** YouTube and Vimeo videos from the core Embed block, and bare video URLs in classic content, get the same placeholder automatically. No content needs rewriting.
* **Privacy-friendly players.** YouTube loads from `youtube-nocookie.com`, Vimeo with `dnt=1`.
* **"Always load" per service.** Visitors can tick a box to load, say, YouTube on every page from then on. The choice is stored only in their browser (localStorage). No cookie, nothing on the server.
* **WP Consent API support.** If your consent banner implements the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), embeds load as soon as the visitor accepts the matching category, without a second click.
* **Works with page caching.** Every consent decision happens in the browser, so cached pages stay correct.
* **Theme-neutral styling** that follows your text colour in light and dark themes.
* No settings page, no database writes, and the plugin itself makes no external requests.

= For developers =

Add a provider, or change one, with the `brocode_consent_embed_providers` filter:

`
add_filter( 'brocode_consent_embed_providers', function ( array $providers ): array {
    $providers['example'] = [
        'label'     => 'Example Video',
        'company'   => 'Example Inc.',
        'category'  => 'marketing', // WP Consent API category
        'aspect_ratio' => '16 / 9', // optional; otherwise the block height applies
        'embed_url' => function ( array $parts, string $url ): ?string {
            return $parts['host'] === 'video.example.com' ? $url : null;
        },
    ];
    return $providers;
} );
`

To leave a core oEmbed untouched, return `false` from `brocode_consent_embed_gate_oembed`.

== Installation ==

1. Install and activate the plugin.
2. Add the **Consent Embed** block and paste the embed link, for example from Google Maps' *Share → Embed a map*.
3. Existing YouTube and Vimeo embeds are covered automatically.

== Frequently Asked Questions ==

= What can go inside a Consent Section? =

Any blocks. The section renders them into an inert `<template>` element and inserts them only after consent, so images, iframes and inline scripts inside do not load before that. Scripts or styles a block loads globally, outside its own markup, are not held back. Check that the third party's code sits inside the section, for example in a Custom HTML block.

Embeds inside a section for the same service load together with it. An embed of a different service, for example a YouTube video inside an Instagram section, still asks for its own consent.

= Does this replace my cookie banner? =

No. It makes sure embeds wait for consent. Visitors can give that consent per embed, or your banner can give it through the WP Consent API.

= Which links are accepted? =

Google Maps embed links (`google.com/maps/embed?…`), Google Calendar embed links (`calendar.google.com/calendar/embed?…`), any YouTube or Vimeo video link, and OpenStreetMap embed links (`openstreetmap.org/export/embed.html?…`). Other URLs are not rendered at all, so the block cannot be used to embed arbitrary pages.

= Can visitors withdraw an "always load" choice? =

The choice lives in the visitor's own browser storage. Clearing site data for your domain resets it.

== Screenshots ==

1. A Google Map before consent.
2. A YouTube video from the core Embed block, gated automatically.
3. Block settings in the editor.
4. A Consent Section around custom content, with its service settings.

== Changelog ==

= 1.0.0 =
* First release.
