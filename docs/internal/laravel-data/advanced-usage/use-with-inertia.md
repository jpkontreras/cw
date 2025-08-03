# Use with Inertia

### On this page

1. Lazy properties

Inertia.js lets you quickly build modern single-page React, Vue, and Svelte apps using classic server-side routing and controllers.

Laravel Data works excellent with Inertia.

You can pass a complete data object to an Inertia response:

```
returnInertia::render('Song',SongsData::from($song));
```

## # # Lazy properties

This package supports lazy properties, which can be manually included or excluded.

Inertia has a similar concept called lazy data evaluation, where some properties wrapped in a closure only get evaluated and included in the response when explicitly asked.

Inertia v2 introduced the concept of deferred props, which allows to defer the loading of certain data until after the initial page render.

This package can output specific properties as Inertia lazy or deferred props as such:

```
classSongDataextendsData{publicfunction__construct(publicLazy|string$title,publicLazy|string$artist,publicLazy|string$lyrics,) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself(Lazy::inertia(fn() =>$song->title),Lazy::closure(fn() =>$song->artist)Lazy::inertiaDeferred(fn() =>$song->lyrics)
        );
    }
}
```

We provide three kinds of lazy properties:

- Lazy::inertia() Never included on first visit, optionally included on partial reloads
- Lazy::closure() Always included on first visit, optionally included on partial reloads
- Lazy::inertiaDeferred() Included when ready, optionally included on partial reloads

Now within your JavaScript code, you can include the properties as such:

```
router.reload((url, {only: ['title'],
});
```

### # # Auto lazy Inertia properties

We already saw earlier that the package can automatically make properties Lazy, the same can be done for Inertia properties.

It is possible to rewrite the previous example as follows:

```
useSpatie\LaravelData\Attributes\AutoClosureLazy;useSpatie\LaravelData\Attributes\AutoInertiaLazy;useSpatie\LaravelData\Attributes\AutoInertiaDeferred;classSongDataextendsData{publicfunction__construct(#[AutoInertiaLazy]publicLazy|string$title,#[AutoClosureLazy]publicLazy|string$artist,#[AutoInertiaDeferred]publicLazy|string$lyrics,) {
    }
}
```

If all the properties of a class should be either Inertia or closure lazy, you can use the attributes on the class level:

```
#[AutoInertiaLazy]classSongDataextendsData{publicfunction__construct(publicLazy|string$title,publicLazy|string$artist,) {
    }
}
```

Creating a rule inferrer

Use with Livewire

Help us improve this page

### On this page

- Lazy properties

Flare

An error tracker especially made for Laravel

Learn more

Help us improve this page

- Products
- Open Source
- Courses
- Web Development

VacanciesAboutBlogDocsGuidelinesMerch ↗

Log in

Kruikstraat 22, Box 12

2018 Antwerp, Belgium

info@spatie.be

+32 3 292 56 79

- GitHub
- Instagram
- LinkedIn
- Twitter
- Bluesky
- Mastodon
- YouTube

- Privacy
- Disclaimer

+32 3 292 56 79

Our office is closed now, email us instead

ESC