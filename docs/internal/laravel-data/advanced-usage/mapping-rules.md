# Mapping rules

It is possible to map the names properties going in and out of your data objects using: MapOutputName, MapInputName

and MapName attributes. But sometimes it can be quite hard to follow where which name can be used. Let's go through

some case:

In the data object:

```
classUserDataextendsData{publicfunction__construct(#[MapName('favorite_song')]// name mappingpublicLazy|SongData$song,#[RequiredWith('song')]// In validation rules, use the original namepublicstring$title,) {
     }publicstaticfunctionallowedRequestExcept():?array{return['song',// Use the original name when defining includes, excludes, excepts and only];
     }publicfunctionrules(ValidContext$context):array{return['song'=>'required',// Use the original name when defining validation rules];
    }// ...}
```

When creating a data object:

```
UserData::from(['favorite_song'=> ...,// You can use the mapped or original name here'title'=>'some title']);
```

When adding an include, exclude, except or only:

```
UserData::from(User::first())->except('song');// Always use the original name here
```

Within a request query, you can use the mapped or original name:

```
https://spatie.be/my-account?except[]=favorite_song
```

When validating a data object or getting rules for a data object, always use the original name:

```
$data= ['favorite_song'=> 123,'title'=>'some title',
];UserData::validate($data)UserData::getValidationRules($data)
```

Internal structures

Validation attributes

Help us improve this page

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