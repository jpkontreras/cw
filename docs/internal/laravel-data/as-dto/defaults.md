# Default values

There are a few ways to define default values for a data object. Since a data object is just a regular PHP class, you can use the constructor to set default values:

```
classSongDataextendsData{publicfunction__construct(publicstring$title= 'Never Gonna Give You Up',publicstring$artist= 'Rick Astley',) {
    }
}
```

This works for simple types like strings, integers, floats, booleans, enums and arrays. But what if you want to set a default value for a more complex type like a CarbonImmutable object? You can use the constructor to do this:

```
classSongDataextendsData{#[Date]publicCarbonImmutable|Optional$date;publicfunction__construct(publicstring$title= 'Never Gonna Give You Up',publicstring$artist= 'Rick Astley',) {$this->date=CarbonImmutable::create(1987, 7, 27);
    }
}
```

You can now do the following:

```
SongData::from();SongData::from(['title'=>'Giving Up On Love','date'=>CarbonImmutable::create(1988, 4, 15)]);
```

Even validation will work:

```
SongData::validateAndCreate();SongData::validateAndCreate(['title'=>'Giving Up On Love','date'=>CarbonImmutable::create(1988, 4, 15)]);
```

There are a few conditions for this approach:

- You must always use a sole property, a property within the constructor definition won't work
- The optional type is technically not required, but it's a good idea to use it otherwise the validation won't work
- Validation won't be performed on the default value, so make sure it is valid

Mapping property names

Computed values

Help us improve this page

Mailcoach

Check out our full-featured (self-hosted) email marketing solution

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