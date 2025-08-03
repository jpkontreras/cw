# Creating a cast

### On this page

1. Null
2. Castables
3. Casting iterable values
4. Combining casts and transformers

Casts take simple values and cast them into complex types. For example, 16-05-1994T00:00:00+00 could be cast into a Carbon object with the same date.

A cast implements the following interface:

```
interfaceCast{publicfunctioncast(DataProperty$property,mixed$value,array$properties,CreationContext$context):mixed;
}
```

A cast receives the following:

- property a DataProperty object which represents the property for which the value is cast. You can read more about the internal structures of the package here
- value the value that should be cast
- properties an array of the current properties that will be used to create the data object
- creationContext the context in which the data object is being created you'll find the following info here:
    - dataClass the data class which is being created
    - validationStrategy the validation strategy which is being used
    - mapPropertyNames whether property names should be mapped
    - disableMagicalCreation whether to use the magical creation methods or not
    - ignoredMagicalMethods the magical methods which are ignored
    - casts a collection of global casts

In the end, the cast should return a casted value.

When the cast is unable to cast the value, an Uncastable object should be returned.

## # # Null

A cast like a transformer never receives a null value, this is because the package will always keep a null value as null because we don't want to create values out of thin air. If you want to replace a null value, then use a magic method.

## # # Castables

You may want to allow your application's value objects to define their own custom casting logic. Instead of attaching the custom cast class to your object, you may alternatively attach a value object class that implements the Spatie\LaravelData\Casts\Castable interface:

```
classForgotPasswordRequestextendsData{publicfunction__construct(#[WithCastable(Email::class)]publicEmail$email,) {
    }
}
```

When using Castable classes, you may still provide arguments in the WithCastable attribute. The arguments will be passed to the dataCastUsing method:

```
classDuplicateEmailCheckextendsData{publicfunction__construct(#[WithCastable(Email::class,normalize:true)]publicEmail$email,) {
    }
}
```

By combining "castables" with PHP's anonymous classes, you may define a value object and its casting logic as a single castable object. To accomplish this, return an anonymous class from your value object's dataCastUsing method. The anonymous class should implement the Cast interface:

```
<?phpnamespaceSpatie\LaravelData\Tests\Fakes\Castables;useSpatie\LaravelData\Casts\Cast;useSpatie\LaravelData\Casts\Castable;useSpatie\LaravelData\Support\Creation\CreationContext;useSpatie\LaravelData\Support\DataProperty;classEmailimplementsCastable{publicfunction__construct(publicstring$email) {

  }publicstaticfunctiondataCastUsing(...$arguments):Cast{returnnewclassimplementsCast{publicfunctioncast(DataProperty$property,mixed$value,array$properties,CreationContext$context):mixed{returnnewEmail($value);
        }
    };
  }
}
```

## # # Casting iterable values

We saw earlier that you can cast all sorts of values in an array or Collection which are not data objects, for this to work, you should implement the IterableItemCast interface:

```
interfaceIterableItemCast{publicfunctioncastIterableItem(DataProperty$property,mixed$value,array$properties,CreationContext$context):mixed;
}
```

The castIterableItem method is called for each item in an array or Collection when being cast, you can check the iterableItemType property of DataProperty-&gt;type to get the type the items should be transformed into.

## # # Combining casts and transformers

You can combine casts and transformers in one class:

```
classToUpperCastAndTransformerimplementsCast, Transformer{publicfunctioncast(DataProperty$property,mixed$value,array$properties,CreationContext$context):string{returnstrtoupper($value);
    }publicfunctiontransform(DataProperty$property,mixed$value,TransformationContext$context):string{returnstrtoupper($value);
    }
}
```

Within your data object, you can use the WithCastAndTransformer attribute to use the cast and transformer:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,#[WithCastAndTransformer(SomeCastAndTransformer::class)]publicstring$artist,) {
    }
}
```

Pipeline

Creating a transformer

Help us improve this page

### On this page

- Null
- Castables
- Casting iterable values
- Combining casts and transformers

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