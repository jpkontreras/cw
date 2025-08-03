# Appending properties

It is possible to add some extra properties to your data objects when they are transformed into a resource:

```
SongData::from(Song::first())->additional(['year'=> 1987,
]);
```

This will output the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley','year'=> 1987,
]
```

When using a closure, you have access to the underlying data object:

```
SongData::from(Song::first())->additional(['slug'=>fn(SongData$songData) =>Str::slug($songData->title),
]);
```

Which produces the following array:

```
['name'=>'Never gonna give you up','artist'=>'Rick Astley','slug'=>'never-gonna-give-you-up',
]
```

It is also possible to add extra properties by overwriting the with method within your data object:

```
classSongDataextendsData{publicfunction__construct(publicint$id,publicstring$title,publicstring$artist) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself($song->id,$song->title,$song->artist);
    }publicfunctionwith()
    {return['endpoints'=> ['show'=>action([SongsController::class,'show'],$this->id),'edit'=>action([SongsController::class,'edit'],$this->id),'delete'=>action([SongsController::class,'delete'],$this->id),
            ]
        ];
    }
}
```

Now each transformed data object contains an endpoints key with all the endpoints for that data object:

```
['id'=> 1,'name'=>'Never gonna give you up','artist'=>'Rick Astley','endpoints'=> ['show'=>'https://spatie.be/songs/1','edit'=>'https://spatie.be/songs/1','delete'=>'https://spatie.be/songs/1',
    ],
]
```

Mapping property names

Wrapping

Help us improve this page

Testing Laravel

Learn how to write quality tests in Pest and PHPUnit in our video course

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