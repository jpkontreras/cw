# Computed values

Earlier we saw how default values can be set for a data object, sometimes you want to set a default value based on other properties. For example, you might want to set a full\_name property based on a first\_name and last\_name property. You can do this by using a computed property:

```
useSpatie\LaravelData\Attributes\Computed;classSongDataextendsData{#[Computed]publicstring$full_name;publicfunction__construct(publicstring$first_name,publicstring$last_name,) {$this->full_name="{$this->first_name} {$this->last_name}";
    }
}
```

You can now do the following:

```
SongData::from(['first_name'=>'Ruben','last_name'=>'Van Assche']);
```

Please notice: the computed property won't be reevaluated when its dependencies change. If you want to update a computed property, you'll have to create a new object.

Again there are a few conditions for this approach:

- You must always use a sole property, a property within the constructor definition won't work
- Computed properties cannot be defined in the payload, a CannotSetComputedValue will be thrown if this is the case
- If the ignore\_exception\_when\_trying\_to\_set\_computed\_property\_value configuration option is set to true, the computed property will be silently ignored when trying to set it in the payload and no CannotSetComputedValue exception will be thrown.

Default values

From a request

Help us improve this page

Laravel beyond CRUD

Check out our course on Laravel development for large apps

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