# Nesting Data

### On this page

1. Validating a nested collection of data objects
2. Nullable and Optional nested data

A data object can contain other data objects or collections of data objects. The package will make sure that also for these data objects validation rules will be generated.

Let's take a look again at the data object from the nesting section:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicArtistData$artist,) {
    }
}
```

The validation rules for this class would be:

```
['title'=> ['required','string'],'artist'=> ['array'],'artist.name'=> ['required','string'],'artist.age'=> ['required','integer'],
]
```

## # # Validating a nested collection of data objects

When validating a data object like this

```
classAlbumDataextendsData{/**
    *@paramarray<int, SongData>$songs*/publicfunction__construct(publicstring$title,publicarray$songs,) {
    }
}
```

In this case the validation rules for AlbumData would look like this:

```
['title'=> ['required','string'],'songs'=> ['present','array',newNestedRules()],
]
```

The NestedRules class is a Laravel validation rule that will validate each item within the collection for the rules defined on the data class for that collection.

## # # Nullable and Optional nested data

If we make the nested data object nullable, the validation rules will change depending on the payload provided:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,public?ArtistData$artist,) {
    }
}
```

If no value for the nested object key was provided or the value is null, the validation rules will be:

```
['title'=> ['required','string'],'artist'=> ['nullable'],
]
```

If, however, a value was provided (even an empty array), the validation rules will be:

```
['title'=> ['required','string'],'artist'=> ['array'],'artist.name'=> ['required','string'],'artist.age'=> ['required','integer'],
]
```

The same happens when a property is made optional:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicArtistData$artist,) {
    }
}
```

There's a small difference compared against nullable, though. When no value was provided for the nested object key, the validation rules will be:

```
['title'=> ['required','string'],'artist'=> ['present','array',newNestedRules()],
]
```

However, when a value was provided (even an empty array or null), the validation rules will be:

```
['title'=> ['required','string'],'artist'=> ['array'],'artist.name'=> ['required','string'],'artist.age'=> ['required','integer'],
]
```

We've written a blog post on the reasoning behind these variable validation rules based upon payload. And they are also the reason why calling getValidationRules on a data object always requires a payload to be provided.

Working with the validator

Skipping validation

Help us improve this page

### On this page

- Validating a nested collection of data objects
- Nullable and Optional nested data

Ray

Debug your applications faster

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