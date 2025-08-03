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

Help us improve this page

### On this page

- Overwriting messages
- Overwriting attributes
- Overwriting other validation functionality
- Overwriting the validator

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