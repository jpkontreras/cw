# From data to array

### On this page

1. Using collections
2. Nesting

A data object can automatically be transformed into an array as such:

```
SongData::from(Song::first())->toArray();
```

Which will output the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley']
```

By default, calling toArray on a data object will recursively transform all properties to an array. This means that nested data objects and collections of data objects will also be transformed to arrays. Other complex types like Carbon, DateTime, Enums, etc... will be transformed into a string. We'll see in the transformers section how to configure and customize this behavior.

If you only want to transform a data object to an array without transforming the properties, you can call the all method:

```
SongData::from(Song::first())->all();
```

You can also manually transform a data object to JSON:

```
SongData::from(Song::first())->toJson();
```

## # # Using collections

Here's how to create a collection of data objects:

```
SongData::collect(Song::all());
```

A collection can be transformed to array:

```
SongData::collect(Song::all())->toArray();
```

Which will output the following array:

```
[
    ["name":"Never Gonna Give You Up","artist":"Rick Astley"],
    ["name":"Giving Up on Love","artist":"Rick Astley"] 
]
```

## # # Nesting

It is possible to nest data objects.

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicstring$email,publicSongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,$user->email,SongData::from($user->favorite_song)
        );
    }
}
```

When transformed to an array, this will look like the following:

```
["name":"Ruben","email":"ruben@spatie.be","favorite_song": ["name":"Never Gonna Give You Up","artist":"Rick Astley"]
]
```

You can also nest a collection of data objects:

```
classAlbumDataextendsData{/**
    *@paramCollection<int, SongData>$songs*/publicfunction__construct(publicstring$title,publicarray$songs,) {
    }publicstaticfunctionfromModel(Album$album):self{returnnewself($album->title,SongData::collect($album->songs)
        );
    }
}
```

As always, remember to type collections of data objects by annotation or the DataCollectionOf attribute, this is essential to transform these collections correctly.

Skipping validation

From data to resource

Help us improve this page

### On this page

- Using collections
- Nesting

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