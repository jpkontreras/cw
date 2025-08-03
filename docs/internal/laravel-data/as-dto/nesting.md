# Nesting

### On this page

1. Collections of data objects

It is possible to nest multiple data objects:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,publicint$age,) {
    }
}classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicArtistData$artist,) {
    }
}
```

You can now create a data object as such:

```
newAlbumData('Never gonna give you up',newArtistData('Rick Astley', 22)
);
```

Or you could create it from an array using a magic creation method:

```
AlbumData::from(['title'=>'Never gonna give you up','artist'=> ['name'=>'Rick Astley','age'=> 22
    ]
]);
```

## # # Collections of data objects

What if you want to nest a collection of data objects within a data object?

That's perfectly possible, but there's a small catch; you should always define what kind of data objects will be stored

within the collection. This is really important later on to create validation rules for data objects or partially

transforming data objects.

There are a few different ways to define what kind of data objects will be stored within a collection. You could use an

annotation, for example, which has an advantage that your IDE will have better suggestions when working with the data

object. And as an extra benefit, static analyzers like PHPStan will also be able to detect errors when your code

is using the wrong types.

A collection of data objects defined by annotation looks like this:

```
/**
 *@property\App\Data\SongData[]$songs*/classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicarray$songs,) {
    }
}
```

or like this when using properties:

```
classAlbumDataextendsData{publicstring$title;/**@var\App\Data\SongData[]*/publicarray$songs;
}
```

If you've imported the data class you can use the short notation:

```
useApp\Data\SongData;classAlbumDataextendsData{/**@varSongData[]*/publicarray$songs;
}
```

It is also possible to use generics:

```
useApp\Data\SongData;classAlbumDataextendsData{/**@vararray<SongData>*/publicarray$songs;
}
```

The same is true for Laravel collections, but be sure to use two generic parameters to describe the collection. One for the collection key type and one for the data object type.

```
useApp\Data\SongData;useIlluminate\Support\Collection;classAlbumDataextendsData{/**@varCollection<int, SongData>*/publicCollection$songs;
}
```

If the collection is well-annotated, the Data class doesn't need to use annotations:

```
/**
 *@templateTKeyof array-key
 *@templateTDataof \App\Data\SongData
 *
 *@extends\Illuminate\Support\Collection<TKey,TData>
 */classSongDataCollectionextendsCollection{
}classAlbumDataextendsData{publicfunction__construct(publicstring$title,publicSongDataCollection$songs,) {
    }
}
```

You can also use an attribute to define the type of data objects that will be stored within a collection:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicarray$songs,) {
    }
}
```

This was the old way to define the type of data objects that will be stored within a collection. It is still supported, but we recommend using the annotation.

Creating a data object

Collections

Help us improve this page

### On this page

- Collections of data objects

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