# Wrapping

### On this page

1. Wrapping collections
2. Nested wrapping
3. Disabling wrapping
4. Getting a wrapped array

By default, when a data object is transformed into JSON in your controller, it looks like this:

```
{"name":"Never gonna give you up","artist":"Rick Astley"}
```

It is possible to wrap a data object:

```
SongData::from(Song::first())->wrap('data');
```

Now the JSON looks like this:

```
{"data":{"name":"Never gonna give you up","artist":"Rick Astley"}}
```

Data objects and collections will only get wrapped when you're sending them as a response and never when calling toArray or toJson on it.

It is possible to define a default wrap key inside a data object:

```
classSongDataextendsData{publicfunctiondefaultWrap():string{return'data';
    }// ...}
```

Or you can set a global wrap key inside the data.php config file:

```
/*
     * Data objects can be wrapped into a key like 'data' when used as a resource,
     * this key can be set globally here for all data objects. You can pass in
     * `null` if you want to disable wrapping.
     */'wrap'=>'data',
```

## # # Wrapping collections

Collections can be wrapped just like data objects:

```
SongData::collect(Song::all(),DataCollection::class)->wrap('data');
```

Notice here, for now we only support wrapping DataCollections, PaginatedDataCollections and CursorPaginatedDataCollections on the root level. Wrapping won't work for Laravel Collections or arrays (for now) since the package cannot interfere. Nested properties with such types can be wrapped though (see further).

The JSON will now look like this:

```
{"data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}]}
```

It is possible to set the data key in paginated collections:

```
SongData::collect(Song::paginate(),PaginatedDataCollection::class)->wrap('paginated_data');
```

Which will let the JSON look like this:

```
{"paginated_data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}],"meta":{"current_page": 1,"first_page_url":"https://spatie.be/?page=1","from": 1,"last_page": 7,"last_page_url":"https://spatie.be/?page=7","next_page_url":"https://spatie.be/?page=2","path":"https://spatie.be/","per_page": 15,"prev_page_url": null,"to": 15,"total": 100}}
```

## # # Nested wrapping

A data object included inside another data object will never be wrapped even if a wrap is set:

```
classUserDataextendsData{publicfunction__construct(publicstring$title,publicstring$email,publicSongData$favorite_song,) {
    }publicstaticfunctionfromModel(User$user):self{returnnewself($user->title,$user->email,SongData::create($user->favorite_song)->wrap('data')
        );
    }
}UserData::from(User::first())->wrap('data');
```

```
{"data":{"name":"Ruben","email":"ruben@spatie.be","favorite_song":{"name":"Never Gonna Give You Up","artist":"Rick Astley"}}}
```

A data collection inside a data object will get wrapped when a wrapping key is set (in order to mimic Laravel resources):

```
useSpatie\LaravelData\Attributes\DataCollectionOf;useSpatie\LaravelData\DataCollection;classAlbumDataextendsData{publicfunction__construct(publicstring$title,#[DataCollectionOf(SongData::class)]publicDataCollection$songs,) {
    }publicstaticfunctionfromModel(Album$album):self{returnnewself($album->title,SongData::collect($album->songs,DataCollection::class)->wrap('data')
        );
    }
}AlbumData::from(Album::first())->wrap('data');
```

The JSON will look like this:

```
{"data":{"title":"Whenever You Need Somebody","songs":{"data":[{"name":"Never Gonna Give You Up","artist":"Rick Astley"},{"name":"Giving Up on Love","artist":"Rick Astley"}]}}}
```

## # # Disabling wrapping

Whenever a data object is wrapped due to the default wrap method or a global wrap key, it is possible to disable wrapping on a data object/collection:

```
SongData::from(Song::first())->withoutWrapping();
```

## # # Getting a wrapped array

By default, toArray and toJson will never wrap a data object or collection, but it is possible to get a wrapped array:

```
SongData::from(Song::first())->wrap('data')->transform(wrapExecutionType:WrapExecutionType::Enabled);
```

Appending properties

Including and excluding properties

Help us improve this page

### On this page

- Wrapping collections
- Nested wrapping
- Disabling wrapping
- Getting a wrapped array

Medialibrary.pro

UI components for the Media Library

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