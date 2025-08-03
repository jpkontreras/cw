# Using validation attributes

### On this page

1. Referencing route parameters
2. Referencing the current authenticated user
3. Referencing container dependencies
4. Referencing other fields
5. Rule attribute
6. Creating your validation attribute

It is possible to add extra rules as attributes to properties of a data object:

```
classSongDataextendsData{publicfunction__construct(#[Uuid()]publicstring$uuid,#[Max(15),IP,StartsWith('192.')]publicstring$ip,) {
    }
}
```

These rules will be merged together with the rules that are inferred from the data object.

So it is not required to add the required and string rule, these will be added automatically. The rules for the

above data object will look like this:

```
['uuid'=> ['required','string','uuid'],'ip'=> ['required','string','max:15','ip','starts_with:192.'],
]
```

For each Laravel validation rule we've got a matching validation attribute, you can find a list of

them here.

## # # Referencing route parameters

Sometimes you need a value within your validation attribute which is a route parameter.

Like the example below where the id should be unique ignoring the current id:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[Unique('songs',ignore:newRouteParameterReference('song'))]publicint$id,) {
    }
}
```

If the parameter is a model and another property should be used, then you can do the following:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[Unique('songs',ignore:newRouteParameterReference('song','uuid'))]publicstring$uuid,) {
    }
}
```

## # # Referencing the current authenticated user

If you need to reference the current authenticated user in your validation attributes, you can use the

AuthenticatedUserReference:

```
useSpatie\LaravelData\Support\Validation\References\AuthenticatedUserReference;classUserDataextendsData{publicfunction__construct(publicstring$name,#[Unique('users','email',ignore:newAuthenticatedUserReference())]publicstring$email,) {   
    }
}
```

When you need to reference a specific property of the authenticated user, you can do so like this:

```
classSongDataextendsData{publicfunction__construct(#[Max(newAuthenticatedUserReference('max_song_title_length'))]publicstring$title,) {
    }
}
```

Using a different guard than the default one can be done by passing the guard name to the constructor:

```
classUserDataextendsData{publicfunction__construct(publicstring$name,#[Unique('users','email',ignore:newAuthenticatedUserReference(guard:'api'))]publicstring$email,) {   
    }
}
```

## # # Referencing container dependencies

If you need to reference a container dependency in your validation attributes, you can use the ContainerReference:

```
useSpatie\LaravelData\Support\Validation\References\ContainerReference;classSongDataextendsData{publicfunction__construct(publicstring$title,#[Max(newContainerReference('max_song_title_length'))]publicstring$artist,) {
    }
}
```

It might be more useful to use a property of the container dependency, which can be done like this:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[Max(newContainerReference(SongSettings::class,'max_song_title_length'))]publicstring$artist,) {
    }
}
```

When your dependency requires specific parameters, you can pass them along:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[Max(newContainerReference(SongSettings::class,'max_song_title_length',parameters: ['repository'=>'redis']))]publicstring$artist,) {
    }
}
```

## # # Referencing other fields

It is possible to reference other fields in validation attributes:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[RequiredIf('title','Never Gonna Give You Up')]publicstring$artist,) {
    }
}
```

These references are always relative to the current data object. So when being nested like this:

```
classAlbumDataextendsData{publicfunction__construct(publicstring$album_name,publicSongData$song,) {
    }
}
```

The generated rules will look like this:

```
['album_name'=> ['required','string'],'songs'=> ['required','array'],'song.title'=> ['required','string'],'song.artist'=> ['string','required_if:song.title,"Never Gonna Give You Up"'],
]
```

If you want to reference fields starting from the root data object you can do the following:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[RequiredIf(newFieldReference('album_name',fromRoot:true),'Whenever You Need Somebody')]publicstring$artist,) {
    }
}
```

The rules will now look like this:

```
['album_name'=> ['required','string'],'songs'=> ['required','array'],'song.title'=> ['required','string'],'song.artist'=> ['string','required_if:album_name,"Whenever You Need Somebody"'],
]
```

## # # Rule attribute

One special attribute is the Rule attribute. With it, you can write rules just like you would when creating a custom

Laravel request:

```
// using an array#[Rule(['required','string'])]publicstring$property// using a string#[Rule('required|string')]publicstring$property// using multiple arguments#[Rule('required','string')]publicstring$property
```

## # # Creating your validation attribute

It is possible to create your own validation attribute by extending the CustomValidationAttribute class, this class

has a getRules method that returns the rules that should be applied to the property.

```
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]classCustomRuleextendsCustomValidationAttribute{/**
     *@returnarray<object|string>|object|string*/publicfunctiongetRules(ValidationPath$path):array|object|string{return[newCustomRule()];
    }
}
```

Quick note: you can only use these rules as an attribute, not as a class rule within the static rules method of the

data class.

Auto rule inferring

Manual rules

Help us improve this page

### On this page

- Referencing route parameters
- Referencing the current authenticated user
- Referencing container dependencies
- Referencing other fields
- Rule attribute
- Creating your validation attribute

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