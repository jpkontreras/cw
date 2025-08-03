# Auto rule inferring

The package will automatically infer validation rules from the data object. For example, for the following data class:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,publicint$age,public?string$genre,) {
    }
}
```

The package will generate the following validation rules:

```
['name'=> ['required','string'],'age'=> ['required','integer'],'genre'=> ['nullable','string'],
]
```

All these rules are inferred by RuleInferrers, special classes that will look at the types of properties and will add rules based upon that.

Rule inferrers are configured in the data.php config file:

```
/*
 * Rule inferrers can be configured here. They will automatically add
 * validation rules to properties of a data object based upon
 * the type of the property.
 */'rule_inferrers'=> [Spatie\LaravelData\RuleInferrers\SometimesRuleInferrer::class,Spatie\LaravelData\RuleInferrers\NullableRuleInferrer::class,Spatie\LaravelData\RuleInferrers\RequiredRuleInferrer::class,Spatie\LaravelData\RuleInferrers\BuiltInTypesRuleInferrer::class,Spatie\LaravelData\RuleInferrers\AttributesRuleInferrer::class,
],
```

By default, five rule inferrers are enabled:

- SometimesRuleInferrer will add a sometimes rule when the property is optional
- NullableRuleInferrer will add a nullable rule when the property is nullable
- RequiredRuleInferrer will add a required rule when the property is not nullable
- BuiltInTypesRuleInferrer will add a rules which are based upon the built-in php types:
  - An int or float type will add the numeric rule
  - A bool type will add the boolean rule
  - A string type will add the string rule
  - A array type will add the array rule
- AttributesRuleInferrer will make sure that rule attributes we described above will also add their rules

It is possible to write your rule inferrers. You can find more information here.

Introduction

Using validation attributes






# Introduction

### On this page

1. When does validation happen?
2. A quick glance at the validation functionality
3. Validation of nested data objects
4. Validation of nested data collections
5. Default values
6. Mapping property names
7. Retrieving validation rules for a data object

Laravel data, allows you to create data objects from all sorts of data. One of the most common ways to create a data object is from a request, and the data from a request cannot always be trusted.

That's why it is possible to validate the data before creating the data object. You can validate requests but also arrays and other structures.

The package will try to automatically infer validation rules from the data object, so you don't have to write them yourself. For example, a ?string property will automatically have the nullable and string rules.

### # # Important notice

Validation is probably one of the coolest features of this package, but it is also the most complex one. We'll try to make it as straightforward as possible to validate data, but in the end, the Laravel validator was not written to be used in this way. So there are some limitations and quirks you should be aware of.

In a few cases it might be easier to just create a custom request class with validation rules and then call toArray on the request to create a data object than trying to validate the data with this package.

## # # When does validation happen?

Validation will always happen BEFORE a data object is created, once a data object is created, it is assumed that the data is valid.

At the moment, there isn't a way to validate data objects, so you should implement this logic yourself. We're looking into ways to make this easier in the future.

Validation runs automatically in the following cases:

- When injecting a data object somewhere and the data object gets created from the request
- When calling the from method on a data object with a request

On all other occasions, validation won't run automatically. You can always validate the data manually by calling the validate method on a data object:

```
SongData::validate(
    ['title'=>'Never gonna give you up']
);// ValidationException will be thrown because 'artist' is missing
```

When you also want to create the object after validation was successful you can use validateAndCreate:

```
SongData::validateAndCreate(
    ['title'=>'Never gonna give you up','artist'=>'Rick Astley']
);// returns a SongData object
```

### # # Validate everything

It is possible to validate all payloads injected or passed to the from method by setting the validation_strategy config option to Always:

```
'validation_strategy'=>\Spatie\LaravelData\Support\Creation\ValidationStrategy::Always->value,
```

Completely disabling validation can be done by setting the validation_strategy config option to Disabled:

```
'validation_strategy'=>\Spatie\LaravelData\Support\Creation\ValidationStrategy::Disabled->value,
```

If you require a more fine-grained control over when validation should happen, you can use data factories to manually specify the validation strategy.

## # # A quick glance at the validation functionality

We've got a lot of documentation about validation and we suggest you read it all, but if you want to get a quick glance at the validation functionality, here's a quick overview:

### # # Auto rule inferring

The package will automatically infer validation rules from the data object. For example, for the following data class:

```
classArtistDataextendsData{publicfunction__construct(publicstring$name,publicint$age,public?string$genre,) {
    }
}
```

The package will generate the following validation rules:

```
['name'=> ['required','string'],'age'=> ['required','integer'],'genre'=> ['nullable','string'],
]
```

The package follows an algorithm to infer rules from the data object. You can read more about it here.

### # # Validation attributes

It is possible to add extra rules as attributes to properties of a data object:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[Max(20)]publicstring$artist,) {
    }
}
```

When you provide an artist with a length of more than 20 characters, the validation will fail.

There's a complete chapter dedicated to validation attributes.

### # # Manual rules

Sometimes you want to add rules manually, this can be done as such:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionrules():array{return['title'=> ['required','string'],'artist'=> ['required','string'],
        ];
    }
}
```

You can read more about manual rules in its dedicated chapter.

### # # Using the container

You can resolve a data object from the container.

```
app(SongData::class);
```

We resolve a data object from the container, its properties will already be filled by the values of the request with matching key names.

If the request contains data that is not compatible with the data object, a validation exception will be thrown.

### # # Working with the validator

We provide a few points where you can hook into the validation process. You can read more about it in the dedicated chapter.

It is for example to:

- overwrite validation messages &amp; attributes
- overwrite the validator itself
- overwrite the redirect when validation fails
- allow stopping validation after a failure
- overwrite the error bag

### # # Authorizing a request

Just like with Laravel requests, it is possible to authorize an action for certain people only:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionauthorize():bool{returnAuth::user()->name==='Ruben';
    }
}
```

If the method returns false, then an AuthorizationException is thrown.

## # # Validation of nested data objects

When a data object is nested inside another data object, the validation rules will also be generated for that nested object.

```
classSingleData{publicfunction__construct(publicArtistData$artist,publicSongData$song,) {
    }
}
```

The validation rules for this class will be:

```
['artist'=> ['array'],'artist.name'=> ['required','string'],'artist.age'=> ['required','integer'],'artist.genre'=> ['nullable','string'],'song'=> ['array'],'song.title'=> ['required','string'],'song.artist'=> ['required','string'],
]
```

There are a few quirky things to keep in mind when working with nested data objects, you can read all about it here.

## # # Validation of nested data collections

Let's say we want to create a data object like this from a request:

```
classAlbumDataextendsData{/**
    *@paramarray<SongData>$songs*/publicfunction__construct(publicstring$title,publicarray$songs,) {
    }
}
```

Since the SongData has its own validation rules, the package will automatically apply them when resolving validation

rules for this object.

In this case the validation rules for AlbumData would look like this:

```
['title'=> ['required','string'],'songs'=> ['required','array'],'songs.*.title'=> ['required','string'],'songs.*.artist'=> ['required','string'],
]
```

More info about nested data collections can be found here.

## # # Default values

When you've set some default values for a data object, the validation rules will only be generated if something else than the default is provided.

For example, when we have this data object:

```
classSongDataextendsData{publicfunction__construct(publicstring$title= 'Never Gonna Give You Up',publicstring$artist= 'Rick Astley',) {
    }
}
```

And we try to validate the following data:

```
SongData::validate(
    ['title'=>'Giving Up On Love']
);
```

Then the validation rules will be:

```
['title'=> ['required','string'],
]
```

## # # Mapping property names

When mapping property names, the validation rules will be generated for the mapped property name:

```
classSongDataextendsData{publicfunction__construct(#[MapInputName('song_title')]publicstring$title,) {
    }
}
```

The validation rules for this class will be:

```
['song_title'=> ['required','string'],
]
```

There's one small catch when the validation fails; the error message will be for the original property name, not the mapped property name. This is a small quirk we hope to solve as soon as possible.

## # # Retrieving validation rules for a data object

You can retrieve the validation rules a data object will generate as such:

```
AlbumData::getValidationRules($payload);
```

This will produce the following array with rules:

```
['title'=> ['required','string'],'songs'=> ['required','array'],'songs.*.title'=> ['required','string'],'songs.*.artist'=> ['required','string'],
]
```

### # # Payload requirement

We suggest always providing a payload when generating validation rules. Because such a payload is used to determine which rules will be generated and which can be skipped.

Factories

Auto rule inferring






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






# Skipping validation

### On this page

1. Skipping validation for all properties

Sometimes you don't want properties to be automatically validated, for instance when you're manually overwriting the

rules method like this:

```
classSongDataextendsData{publicfunction__construct(publicstring$name,) {
    }publicstaticfunctionfromRequest(Request$request):static{returnnewself("{$request->input('first_name')} {$request->input('last_name')}")
    }publicstaticfunctionrules():array{return['first_name'=> ['required','string'],'last_name'=> ['required','string'],
        ];
    }
}
```

When a request is being validated, the rules will look like this:

```
['name'=> ['required','string'],'first_name'=> ['required','string'],'last_name'=> ['required','string'],
]
```

We know we never want to validate the name property since it won't be in the request payload, this can be done as

such:

```
classSongDataextendsData{publicfunction__construct(#[WithoutValidation]publicstring$name,) {
    }
}
```

Now the validation rules will look like this:

```
['first_name'=> ['required','string'],'last_name'=> ['required','string'],
]
```

## # # Skipping validation for all properties

By using data factories or setting the validation_strategy in the data.php config you can skip validation for all properties of a data class.

Nesting Data

From data to array






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






# Validation attributes

### On this page

1. Accepted
2. AcceptedIf
3. ActiveUrl
4. After
5. AfterOrEqual
6. Alpha
7. AlphaDash
8. AlphaNumeric
9. ArrayType
10. Bail
11. Before
12. BeforeOrEqual
13. Between
14. BooleanType
15. Confirmed
16. CurrentPassword
17. Date
18. DateEquals
19. DateFormat
20. Declined
21. DeclinedIf
22. Different
23. Digits
24. DigitsBetween
25. Dimensions
26. Distinct
27. DoesntEndWith
28. DoesntStartWith
29. Email
30. EndsWith
31. Enum
32. ExcludeIf
33. ExcludeUnless
34. ExcludeWith
35. ExcludeWithout
36. Exists
37. File
38. Filled
39. GreaterThan
40. GreaterThanOrEqualTo
41. Image
42. In
43. InArray
44. IntegerType
45. IP
46. IPv4
47. IPv6
48. Json
49. LessThan
50. LessThanOrEqualTo
51. Lowercase
52. ListType
53. MacAddress
54. Max
55. MaxDigits
56. MimeTypes
57. Mimes
58. Min
59. MinDigits
60. MultipleOf
61. NotIn
62. NotRegex
63. Nullable
64. Numeric
65. Password
66. Present
67. Prohibited
68. ProhibitedIf
69. ProhibitedUnless
70. Prohibits
71. Regex
72. Required
73. RequiredIf
74. RequiredUnless
75. RequiredWith
76. RequiredWithAll
77. RequiredWithout
78. RequiredWithoutAll
79. RequiredArrayKeys
80. Rule
81. Same
82. Size
83. Sometimes
84. StartsWith
85. StringType
86. TimeZone
87. Unique
88. Uppercase
89. Url
90. Ulid
91. Uuid

These are all the validation attributes currently available in laravel-data.

## # # Accepted

Docs

```
#[Accepted]publicbool$closure;
```

## # # AcceptedIf

Docs

```
#[AcceptedIf('other_field','equals_this')]publicbool$closure;
```

## # # ActiveUrl

Docs

```
#[ActiveUrl]publicstring$closure;
```

## # # After

Docs

```
#[After('tomorrow')]publicCarbon$closure;#[After(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[After(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # AfterOrEqual

Docs

```
#[AfterOrEqual('tomorrow')]publicCarbon$closure;#[AfterOrEqual(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[AfterOrEqual(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # Alpha

Docs

```
#[Alpha]publicstring$closure;
```

## # # AlphaDash

Docs

```
#[AlphaDash]publicstring$closure;
```

## # # AlphaNumeric

Docs

```
#[AlphaNumeric]publicstring$closure;
```

## # # ArrayType

Docs

```
#[ArrayType]publicarray$closure;#[ArrayType(['valid_key','other_valid_key'])]publicarray$closure;#[ArrayType('valid_key','other_valid_key')]publicarray$closure;
```

## # # Bail

Docs

```
#[Bail]publicstring$closure;
```

## # # Before

Docs

```
#[Before('tomorrow')]publicCarbon$closure;#[Before(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[Before(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # BeforeOrEqual

Docs

```
#[BeforeOrEqual('tomorrow')]publicCarbon$closure;#[BeforeOrEqual(Carbon::yesterday())]publicCarbon$closure;// Always use field references when referencing other fields#[BeforeOrEqual(newFieldReference('other_field'))]publicCarbon$closure;
```

## # # Between

Docs

```
#[Between(3.14, 42)]publicint$closure;
```

## # # BooleanType

Docs

```
#[BooleanType]publicbool$closure;
```

## # # Confirmed

Docs

```
#[Confirmed]publicstring$closure;
```

## # # CurrentPassword

Docs

```
#[CurrentPassword]publicstring$closure;#[CurrentPassword('api')]publicstring$closure;
```

## # # Date

Docs

```
#[Date]publicCarbon$date;
```

## # # DateEquals

Docs

```
#[DateEquals('tomorrow')]publicCarbon$date;#[DateEquals(Carbon::yesterday())]publicCarbon$date;
```

## # # DateFormat

Docs

```
#[DateFormat('d-m-Y')]publicCarbon$date;#[DateFormat(['Y-m-d','Y-m-d H:i:s'])]publicCarbon$date;
```

## # # Declined

Docs

```
#[Declined]publicbool$closure;
```

## # # DeclinedIf

Docs

```
#[DeclinedIf('other_field','equals_this')]publicbool$closure;
```

## # # Different

Docs

```
#[Different('other_field')]publicstring$closure;
```

## # # Digits

Docs

```
#[Digits(10)]publicint$closure;
```

## # # DigitsBetween

Docs

```
#[DigitsBetween(2, 10)]publicint$closure;
```

## # # Dimensions

Docs

```
#[Dimensions(ratio: 1.5)]publicUploadedFile$closure;#[Dimensions(maxWidth: 100,maxHeight: 100)]publicUploadedFile$closure;
```

## # # Distinct

Docs

```
#[Distinct]publicstring$closure;#[Distinct(Distinct::Strict)]publicstring$closure;#[Distinct(Distinct::IgnoreCase)]publicstring$closure;
```

## # # DoesntEndWith

Docs

```
#[DoesntEndWith('a')]publicstring$closure;#[DoesntEndWith(['a','b'])]publicstring$closure;#[DoesntEndWith('a','b')]publicstring$closure;
```

## # # DoesntStartWith

Docs

```
#[DoesntStartWith('a')]publicstring$closure;#[DoesntStartWith(['a','b'])]publicstring$closure;#[DoesntStartWith('a','b')]publicstring$closure;
```

## # # Email

Docs

```
#[Email]publicstring$closure;#[Email(Email::RfcValidation)]publicstring$closure;#[Email([Email::RfcValidation,Email::DnsCheckValidation])]publicstring$closure;#[Email(Email::RfcValidation,Email::DnsCheckValidation)]publicstring$closure;
```

## # # EndsWith

Docs

```
#[EndsWith('a')]publicstring$closure;#[EndsWith(['a','b'])]publicstring$closure;#[EndsWith('a','b')]publicstring$closure;
```

## # # Enum

Docs

```
#[Enum(ChannelType::class)]publicstring$closure;#[Enum(ChannelType::class,only: [ChannelType::Email])]publicstring$closure;#[Enum(ChannelType::class,except: [ChannelType::Email])]publicstring$closure;
```

## # # ExcludeIf

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeIf('other_field','has_value')]publicstring$closure;
```

## # # ExcludeUnless

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeUnless('other_field','has_value')]publicstring$closure;
```

## # # ExcludeWith

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeWith('other_field')]publicstring$closure;
```

## # # ExcludeWithout

At the moment the data is not yet excluded due to technical reasons, v4 should fix this

Docs

```
#[ExcludeWithout('other_field')]publicstring$closure;
```

## # # Exists

Docs

```
#[Exists('users')]publicstring$closure;#[Exists(User::class)]publicstring$closure;#[Exists('users','email')]publicstring$closure;#[Exists('users','email',connection:'tenant')]publicstring$closure;#[Exists('users','email',withoutTrashed:true)]publicstring$closure;
```

## # # File

Docs

```
#[File]publicUploadedFile$closure;
```

## # # Filled

Docs

```
#[Filled]publicstring$closure;
```

## # # GreaterThan

Docs

```
#[GreaterThan('other_field')]publicint$closure;
```

## # # GreaterThanOrEqualTo

Docs

```
#[GreaterThanOrEqualTo('other_field')]publicint$closure;
```

## # # Image

Docs

```
#[Image]publicUploadedFile$closure;
```

## # # In

Docs

```
#[In([1, 2, 3,'a','b'])]publicmixed$closure;#[In(1, 2, 3,'a','b')]publicmixed$closure;
```

## # # InArray

Docs

```
#[InArray('other_field')]publicstring$closure;
```

## # # IntegerType

Docs

```
#[IntegerType]publicint$closure;
```

## # # IP

Docs

```
#[IP]publicstring$closure;
```

## # # IPv4

Docs

```
#[IPv4]publicstring$closure;
```

## # # IPv6

Docs

```
#[IPv6]publicstring$closure;
```

## # # Json

Docs

```
#[Json]publicstring$closure;
```

## # # LessThan

Docs

```
#[LessThan('other_field')]publicint$closure;
```

## # # LessThanOrEqualTo

Docs

```
#[LessThanOrEqualTo('other_field')]publicint$closure;
```

## # # Lowercase

Docs

```
#[Lowercase]publicstring$closure;
```

## # # ListType

Docs

```
#[ListType]publicarray$array;
```

## # # MacAddress

Docs

```
#[MacAddress]publicstring$closure;
```

## # # Max

Docs

```
#[Max(20)]publicint$closure;
```

## # # MaxDigits

Docs

```
#[MaxDigits(10)]publicint$closure;
```

## # # MimeTypes

Docs

```
#[MimeTypes('video/quicktime')]publicUploadedFile$closure;#[MimeTypes(['video/quicktime','video/avi'])]publicUploadedFile$closure;#[MimeTypes('video/quicktime','video/avi')]publicUploadedFile$closure;
```

## # # Mimes

Docs

```
#[Mimes('jpg')]publicUploadedFile$closure;#[Mimes(['jpg','png'])]publicUploadedFile$closure;#[Mimes('jpg','png')]publicUploadedFile$closure;
```

## # # Min

Docs

```
#[Min(20)]publicint$closure;
```

## # # MinDigits

Docs

```
#[MinDigits(2)]publicint$closure;
```

## # # MultipleOf

Docs

```
#[MultipleOf(3)]publicint$closure;
```

## # # NotIn

Docs

```
#[NotIn([1, 2, 3,'a','b'])]publicmixed$closure;#[NotIn(1, 2, 3,'a','b')]publicmixed$closure;
```

## # # NotRegex

Docs

```
#[NotRegex('/^.+$/i')]publicstring$closure;
```

## # # Nullable

Docs

```
#[Nullable]public?string$closure;
```

## # # Numeric

Docs

```
#[Numeric]public?string$closure;
```

## # # Password

Docs

```
#[Password(min: 12,letters:true,mixedCase:true,numbers:false,symbols:false,uncompromised:true,uncompromisedThreshold: 0)]publicstring$closure;
```

## # # Present

Docs

```
#[Present]publicstring$closure;
```

## # # Prohibited

Docs

```
#[Prohibited]public?string$closure;
```

## # # ProhibitedIf

Docs

```
#[ProhibitedIf('other_field','has_value')]public?string$closure;#[ProhibitedIf('other_field', ['has_value','or_this_value'])]public?string$closure;
```

## # # ProhibitedUnless

Docs

```
#[ProhibitedUnless('other_field','has_value')]public?string$closure;#[ProhibitedUnless('other_field', ['has_value','or_this_value'])]public?string$closure;
```

## # # Prohibits

Docs

```
#[Prohibits('other_field')]public?string$closure;#[Prohibits(['other_field','another_field'])]public?string$closure;#[Prohibits('other_field','another_field')]public?string$closure;
```

## # # Regex

Docs

```
#[Regex('/^.+$/i')]publicstring$closure;
```

## # # Required

Docs

```
#[Required]publicstring$closure;
```

## # # RequiredIf

Docs

```
#[RequiredIf('other_field','value')]public?string$closure;#[RequiredIf('other_field', ['value','another_value'])]public?string$closure;
```

## # # RequiredUnless

Docs

```
#[RequiredUnless('other_field','value')]public?string$closure;#[RequiredUnless('other_field', ['value','another_value'])]public?string$closure;
```

## # # RequiredWith

Docs

```
#[RequiredWith('other_field')]public?string$closure;#[RequiredWith(['other_field','another_field'])]public?string$closure;#[RequiredWith('other_field','another_field')]public?string$closure;
```

## # # RequiredWithAll

Docs

```
#[RequiredWithAll('other_field')]public?string$closure;#[RequiredWithAll(['other_field','another_field'])]public?string$closure;#[RequiredWithAll('other_field','another_field')]public?string$closure;
```

## # # RequiredWithout

Docs

```
#[RequiredWithout('other_field')]public?string$closure;#[RequiredWithout(['other_field','another_field'])]public?string$closure;#[RequiredWithout('other_field','another_field')]public?string$closure;
```

## # # RequiredWithoutAll

Docs

```
#[RequiredWithoutAll('other_field')]public?string$closure;#[RequiredWithoutAll(['other_field','another_field'])]public?string$closure;#[RequiredWithoutAll('other_field','another_field')]public?string$closure;
```

## # # RequiredArrayKeys

Docs

```
#[RequiredArrayKeys('a')]publicarray$closure;#[RequiredArrayKeys(['a','b'])]publicarray$closure;#[RequiredArrayKeys('a','b')]publicarray$closure;
```

## # # Rule

```
#[Rule('string|uuid')]publicstring$closure;#[Rule(['string','uuid'])]publicstring$closure;
```

## # # Same

Docs

```
#[Same('other_field')]publicstring$closure;
```

## # # Size

Docs

```
#[Size(10)]publicstring$closure;
```

## # # Sometimes

Docs

```
#[Sometimes]publicstring$closure;
```

## # # StartsWith

Docs

```
#[StartsWith('a')]publicstring$closure;#[StartsWith(['a','b'])]publicstring$closure;#[StartsWith('a','b')]publicstring$closure;
```

## # # StringType

Docs

```
#[StringType()]publicstring$closure;
```

## # # TimeZone

Docs

```
#[TimeZone()]publicstring$closure;
```

## # # Unique

Docs

```
#[Unique('users')]publicstring$closure;#[Unique(User::class)]publicstring$closure;#[Unique('users','email')]publicstring$closure;#[Unique('users',connection:'tenant')]publicstring$closure;#[Unique('users',withoutTrashed:true)]publicstring$closure;#[Unique('users',ignore: 5)]publicstring$closure;#[Unique('users',ignore:newAuthenticatedUserReference())]publicstring$closure;#[Unique('posts',ignore:newRouteParameterReference('post'))]publicstring$closure;
```

## # # Uppercase

Docs

```
#[Uppercase]publicstring$closure;
```

## # # Url

Docs

```
#[Url]publicstring$closure;
```

## # # Ulid

Docs

```
#[Ulid]publicstring$closure;
```

## # # Uuid

Docs

```
#[Uuid]publicstring$closure;
```

Mapping rules

Get data from a class quickly






# Working with the validator

### On this page

1. Overwriting messages
2. Overwriting attributes
3. Overwriting other validation functionality
4. Overwriting the validator

Sometimes a more fine-grained control over the validation is required. In such case you can hook into the validator.

## # # Overwriting messages

It is possible to overwrite the error messages that will be returned when an error fails:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionmessages():array{return['title.required'=>'A title is required','artist.required'=>'An artist is required',
        ];
    }
}
```

## # # Overwriting attributes

In the default Laravel validation rules, you can overwrite the name of the attribute as such:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionattributes():array{return['title'=>'titel','artist'=>'artiest',
        ];
    }
}
```

## # # Overwriting other validation functionality

Next to overwriting the validator, attributes and messages it is also possible to overwrite the following functionality.

The redirect when a validation failed:

```
classSongDataextendsData{// ...publicstaticfunctionredirect():string{returnaction(HomeController::class);
    }
}
```

Or the route which will be used to redirect after a validation failed:

```
classSongDataextendsData{// ...publicstaticfunctionredirectRoute():string{return'home';
    }
}
```

Whether to stop validating on the first failure:

```
classSongDataextendsData{// ...publicstaticfunctionstopOnFirstFailure():bool{returntrue;
    }
}
```

The name of the error bag:

```
classSongDataextendsData{// ...publicstaticfunctionerrorBag():string{return'never_gonna_give_an_error_up';
    }
}
```

### # # Using dependencies in overwritten functionality

You can also provide dependencies to be injected in the overwritten validator functionality methods like messages

, attributes, redirect, redirectRoute, stopOnFirstFailure, errorBag:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionattributes(ValidationAttributesLanguageRepository$validationAttributesLanguageRepository):array{return['title'=>$validationAttributesLanguageRepository->get('title'),'artist'=>$validationAttributesLanguageRepository->get('artist'),
        ];
    }
}
```

## # # Overwriting the validator

Before validating the values, it is possible to plugin into the validator. This can be done as such:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionwithValidator(Validator$validator):void{$validator->after(function($validator) {$validator->errors()->add('field','Something is wrong with this field!');
        });
    }
}
```

Please note that this method will only be called on the root data object that is being validated, all the nested data objects and collections withValidator methods will not be called.

Manual rules

Nesting Data
