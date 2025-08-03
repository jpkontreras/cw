# Eloquent casting

### On this page

1. Casting data collections
2. Using defaults for null database values
3. Using encryption with data objects and collections

Since data objects can be created from arrays and be easily transformed into arrays back again, they are excellent to be used

with Eloquent casts:

```
classSongextendsModel{protected$casts= ['artist'=>ArtistData::class,
    ];
}
```

Now you can store a data object in a model as such:

```
Song::create(['artist'=>newArtistData(name:'Rick Astley',age: 22),
]);
```

It is also possible to use an array representation of the data object:

```
Song::create(['artist'=> ['name'=>'Rick Astley','age'=> 22
    ]
]);
```

This will internally be converted to a data object which you can later retrieve as such:

```
Song::findOrFail($id)->artist;// ArtistData object
```

### # # Abstract data objects

Sometimes you have an abstract parent data object with multiple child data objects, for example:

```
abstractclassRecordConfigextendsData{publicfunction__construct(publicint$tracks,) {}
}classCdRecordConfigextendsRecordConfig{publicfunction__construct(int$tracks,publicint$bytes,) {parent::__construct($tracks);
    }
}classVinylRecordConfigextendsRecordConfig{publicfunction__construct(int$tracks,publicint$rpm,) {parent::__construct($tracks);
    }
}
```

A model can have a JSON field which is either one of these data objects:

```
classRecordextendsModel{protected$casts= ['config'=>RecordConfig::class,
    ];
}
```

You can then store either a CdRecordConfig or a VinylRecord in the config field:

```
$cdRecord=Record::create(['config'=>newCdRecordConfig(tracks: 12,bytes: 1000),
]);$vinylRecord=Record::create(['config'=>newVinylRecordConfig(tracks: 12,rpm: 33),
]);$cdRecord->config;// CdRecordConfig object$vinylRecord->config;// VinylRecordConfig object
```

When a data object class is abstract and used as an Eloquent cast, then this feature will work out of the box.

The child data object value of the model will be stored in the database as a JSON string with the class name and the data object properties:

```
{"type":"\\App\\Data\\CdRecordConfig","data":{"tracks": 12,"bytes": 1000}}
```

When retrieving the model, the data object will be instantiated based on the type key in the JSON string.

#### # # Abstract data object with collection

You can use with collection.

```
classRecordextendsModel{protected$casts= ['configs'=>DataCollection::class.':'.RecordConfig::class,
    ];
}
```

#### # # Abstract data class morphs

By default, the type key in the JSON string will be the fully qualified class name of the child data object. This can break your application quite easily when you refactor your code. To prevent this, you can add a morph map like with Eloquent models. Within your AppServiceProvivder you can add the following mapping:

```
useSpatie\LaravelData\Support\DataConfig;app(DataConfig::class)->enforceMorphMap(['cd_record_config'=>CdRecordConfig::class,'vinyl_record_config'=>VinylRecordConfig::class,
]);
```

## # # Casting data collections

It is also possible to store data collections in an Eloquent model:

```
classArtistextendsModel{protected$casts= ['songs'=>DataCollection::class.':'.SongData::class,
    ];
}
```

A collection of data objects within the Eloquent model can be made as such:

```
Artist::create(['songs'=> [newSongData(title:'Never gonna give you up',artist:'Rick Astley'),newSongData(title:'Together Forever',artist:'Rick Astley'),
    ],
]);
```

It is also possible to provide an array instead of a data object to the collection:

```
Artist::create(['songs'=> [
        ['title'=>'Never gonna give you up','artist'=>'Rick Astley'],
        ['title'=>'Together Forever','artist'=>'Rick Astley']
    ],
]);
```

## # # Using defaults for null database values

By default, if a database value is null, then the model attribute will also be null. However, sometimes you might want to instantiate the attribute with some default values.

To achieve this, you may provide an additional default Cast Parameter to ensure the caster gets instantiated.

```
classSongextendsModel{protected$casts= ['artist'=>ArtistData::class.':default',
    ];
}
```

This will ensure that the ArtistData caster is instantiated even when the artist attribute in the database is null.

You may then specify some default values in the cast which will be used instead.

```
classArtistDataextendsData{publicstring$name='Default name';
}
```

```
Song::findOrFail($id)->artist->name;// 'Default name'
```

### # # Nullable collections

You can also use the default argument in the case where you always want a DataCollection to be returned.

The first argument (after :) should always be the data class to be used with the DataCollection, but you can add default as a comma separated second argument.

```
classArtistextendsModel{protected$casts= ['songs'=>DataCollection::class.':'.SongData::class.',default',
    ];
}
```

```
$artist=Artist::create(['songs'=>null]);$artist->songs;// DataCollection$artist->songs->count();// 0
```

## # # Using encryption with data objects and collections

Similar to Laravel's native encrypted casts, you can also encrypt data objects and collections.

When retrieving the model, the data object will be decrypted automatically.

```
classArtistextendsModel{protected$casts= ['songs'=>DataCollection::class.':'.SongData::class.',encrypted',
    ];
}
```

Transforming data

Transforming to TypeScript

Help us improve this page

### On this page

- Casting data collections
- Using defaults for null database values
- Using encryption with data objects and collections

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