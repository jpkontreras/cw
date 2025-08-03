# Creating a data object

### On this page

1. Magical creation
2. Optional creation
3. Creation without magic methods
4. Advanced creation using factories
5. DTO classes

Let's get started with the following simple data object:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

Since this is just a simple PHP object, it can be initialized as such:

```
newSongData('Never gonna give you up','Rick Astley');
```

But with this package, you can initialize the data object also with an array:

```
SongData::from(['title'=>'Never gonna give you up','artist'=>'Rick Astley']);
```

You can use the from method to create a data object from nearly anything. For example, let's say you have an Eloquent

model like this:

```
classSongextendsModel{// Your model code}
```

You can create a data object from such a model like this:

```
SongData::from(Song::firstOrFail($id));
```

The package will find the required properties within the model and use them to construct the data object.

Data can also be created from JSON strings:

```
SongData::from('{"title" : "Never Gonna Give You Up","artist" : "Rick Astley"}');
```

Although the PHP 8.0 constructor properties look great in data objects, it is perfectly valid to use regular properties

without a constructor like so:

```
classSongDataextendsData{publicstring$title;publicstring$artist;
}
```

## # # Magical creation

It is possible to overwrite or extend the behaviour of the from method for specific types. So you can construct a data

object in a specific manner for that type. This can be done by adding a static method starting with 'from' to the data

object.

For example, we want to change how we create a data object from a model. We can add a fromModel static method that

takes the model we want to use as a parameter:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromModel(Song$song):self{returnnewself("{$song->title} ({$song->year})",$song->artist);
    }
}
```

Now when creating a data object from a model like this:

```
SongData::from(Song::firstOrFail($id));
```

Instead of the default method, the fromModel method will be called to create a data object from the found model.

You're truly free to add as many from methods as you want. For example, you could add one to create a data object from a

string:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromString(string$string):self{
        [$title,$artist] =explode('|',$string);returnnewself($title,$artist);
    }
}
```

From now on, you can create a data object like this:

```
SongData::from('Never gonna give you up|Rick Astley');
```

It is also possible to use multiple arguments in a magical creation method:

```
classSongDataextendsData{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }publicstaticfunctionfromMultiple(string$title,string$artist):self{returnnewself($title,$artist);
    }
}
```

Now we can create the data object like this:

```
SongData::from('Never gonna give you up','Rick Astley');
```

There are a few requirements to enable magical data object creation:

- The method must be static and public
- The method must start with from
- The method cannot be called from

When the package cannot find such a method for a type given to the data object's from method. Then the data object

will try to create itself from the following types:

- An Eloquent model by calling toArray on it
- A Laravel request by calling all on it
- An Arrayable by calling toArray on it
- An array

This list can be extended using extra normalizers, find more about

it here.

When a data object cannot be created using magical methods or the default methods, a CannotCreateData

exception will be thrown.

## # # Optional creation

It is impossible to return null from a data object's from method since we always expect a data object when

calling from. To solve this, you can call the optional method:

```
SongData::optional(null);// returns null
```

Underneath the optional method will call the from method when a value is given, so you can still magically create data

objects. When a null value is given, it will return null.

## # # Creation without magic methods

You can ignore the magical creation methods when creating a data object as such:

```
SongData::factory()->withoutMagicalCreation()->from($song);
```

## # # Advanced creation using factories

It is possible to configure how a data object is created, whether it will be validated, which casts to use and more. You

can read more about it here.

## # # DTO classes

The default Data class from which you extend your data objects is a multi versatile class, it packs a lot of

functionality. But sometimes you just want a simple DTO class. You can use the Dto class for this:

```
classSongDataextendsDto{publicfunction__construct(publicstring$title,publicstring$artist,) {
    }
}
```

The Dto class is a data class in its most basic form. It can be created from anything using magical methods, can

validate payloads before creating the data object and can be created using factories. But it doesn't have any of the

other functionality that the Data class has.

Quickstart

Nesting

Help us improve this page

### On this page

- Magical creation
- Optional creation
- Creation without magic methods
- Advanced creation using factories
- DTO classes

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