# Manual rules

### On this page

1. Merging manual rules
2. Using attributes
3. Using context

It is also possible to write rules down manually in a dedicated method on the data object. This can come in handy when you want

to construct a custom rule object which isn't possible with attributes:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules():array{return['title'=> ['required','string'],'artist'=> ['required','string'],
        ];
    }
}
```

By overwriting a property's rules within the rules method, no other rules will be inferred automatically anymore for that property.

This means that in the following example, only a max:20 rule will be added, and not a string and required rule:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules():array{return['title'=> ['max:20'],'artist'=> ['max:20'],
        ];
    }
}// The generated rules will look like this['title'=> ['max:20'],'artist'=> ['max:20'],
]
```

As a rule of thumb always follow these rules:

Always use the array syntax for defining rules and not a single string which spits the rules by | characters.

This is needed when using regexes those | can be seen as part of the regex

## # # Merging manual rules

Writing manual rules doesn't mean that you can't use the automatic rules inferring anymore. By adding the MergeValidationRules attribute to your data class, the rules will be merged:

```
#[MergeValidationRules]classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules():array{return['title'=> ['max:20'],'artist'=> ['max:20'],
        ];
    }
}// The generated rules will look like this['title'=> [required,'string','max:20'],'artist'=> [required,'string','max:20'],
]
```

## # # Using attributes

It is even possible to use the validationAttribute objects within the rules method:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules():array{return['title'=> [newRequired(),newStringType()],'artist'=> [newRequired(),newStringType()],
        ];
    }
}
```

You can even add dependencies to be automatically injected:

```
useSongSettingsRepository;classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules(SongSettingsRepository$settings):array{return['title'=> [newRequiredIf($settings->forUser(auth()->user())->title_required),newStringType()],'artist'=> [newRequired(),newStringType()],
        ];
    }
}
```

## # # Using context

Sometimes a bit more context is required, in such a case a ValidationContext parameter can be injected as such:

Additionally, if you need to access the data payload, you can use $payload parameter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules(ValidationContext$context):array{return['title'=> ['required'],'artist'=>Rule::requiredIf($context->fullPayload['title'] !=='Never Gonna Give You Up'),
        ];
    }
}
```

By default, the provided payload is the whole request payload provided to the data object.

If you want to generate rules in nested data objects, then a relative payload can be more useful:

```
classAlbumDataextendsData{/**
    *@paramarray<SongData>$songs*/publicfunction__construct(publicstring$title,publicarray$songs,) {
    }
}classSongDataextendsData{publicfunction__construct(publicstring$title,public?string$artist,) {
    }publicstaticfunctionrules(ValidationContext$context):array{return['title'=> ['required'],'artist'=>Rule::requiredIf($context->payload['title'] !=='Never Gonna Give You Up'),
        ];
    }
}
```

When providing such a payload:

```
['title'=>'Best songs ever made','songs'=> [
        ['title'=>'Never Gonna Give You Up'],
        ['title'=>'Heroes','artist'=>'David Bowie'],
    ],
];
```

The rules will be:

```
['title'=> ['string','required'],'songs'=> ['present','array'],'songs.*.title'=> ['string','required'],'songs.*.artist'=> ['string','nullable'],'songs.*'=> [NestedRules(...)],
]
```

It is also possible to retrieve the current path in the data object chain we're generating rules for right now by calling $context-&gt;path. In the case of our previous example this would be songs.0 and songs.1;

Make sure the name of the parameter is $context in the rules method, otherwise no context will be injected.

Using validation attributes

Working with the validator

Help us improve this page

### On this page

- Merging manual rules
- Using attributes
- Using context

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