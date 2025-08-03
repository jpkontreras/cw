# Optional properties

Sometimes you have a data object with properties which shouldn't always be set, for example in a partial API update where you only want to update certain fields. In this case you can make a property Optional as such:

```
useSpatie\LaravelData\Optional;classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring|Optional$artist,) {
    }
}
```

You can now create the data object as such:

```
SongData::from(['title'=>'Never gonna give you up']);
```

The value of artist will automatically be set to Optional. When you transform this data object to an array, it will look like this:

```
['title'=>'Never gonna give you up']
```

You can manually use Optional values within magical creation methods as such:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring|Optional$artist,) {
    }publicstaticfunctionfromTitle(string$title):static{returnnewself($title,Optional::create());
    }
}
```

It is possible to automatically update Optional values to null:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicOptional|null|string$artist,) {
    }
}SongData::factory()
    ->withoutOptionalValues()
    ->from(['title'=>'Never gonna give you up']);// artist will `null` instead of `Optional`
```

You can read more about this here.

Casts

Mapping property names

Help us improve this page

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