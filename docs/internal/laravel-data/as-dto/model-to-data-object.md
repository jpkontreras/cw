# From a model

### On this page

1. Casts
2. Attributes &amp;amp; Accessors
3. Mapping property names
4. Relations
5. Missing attributes

It is possible to create a data object from a model, let's say we have the following model:

```
classArtistextendsModel{
    
}
```

It has the following columns in the database:

- id
- first\_name
- last\_name
- created\_at
- updated\_at

We can create a data object from this model like this:

```
classArtistDataextendsData{publicint$id;publicstring$first_name;publicstring$last_name;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

We now can create a data object from the model like this:

```
$artist=ArtistData::from(Artist::find(1));
```

## # # Casts

A model can have casts, these casts will be called before a data object is created. Let's extend the model:

```
classArtistextendsModel{publicfunctioncasts():array{return['properties'=>'array'];
    }
}
```

Within the database the new column will be stored as a JSON string, but in the data object we can just use the array

type:

```
classArtistDataextendsData{publicint$id;publicstring$first_name;publicstring$last_name;publicarray$properties;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

## # # Attributes &amp; Accessors

Laravel allows you to define attributes on a model, these will be called before a data object is created. Let's extend

the model:

```
classArtistextendsModel{publicfunctiongetFullNameAttribute():string{return$this->first_name.' '.$this->last_name;
    }
}
```

We now can use the attribute in the data object:

```
classArtistDataextendsData{publicint$id;publicstring$full_name;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

Remember: we need to use the snake\_case version of the attribute in the data object since that's how it is stored in the

model. Read on for a more elegant solution when you want to use camelCase property names in your data object.

It is also possible to define accessors on a model which are the successor of the attributes:

```
classArtistextendsModel{publicfunctiongetFullName():Attribute{returnAttribute::get(fn() =>"{$this->first_name} {$this->last_name}");
    }
}
```

With the same data object we created earlier we can now use the accessor.

## # # Mapping property names

Sometimes you want to use camelCase property names in your data object, but the model uses snake\_case. You can use

an MapInputName to map the property names:

```
useSpatie\LaravelData\Attributes\MapInputName;useSpatie\LaravelData\Mappers\SnakeCaseMapper;classArtistDataextendsData{publicint$id;#[MapInputName(SnakeCaseMapper::class)]publicstring$fullName;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

An even more elegant solution would be to map every property within the data object:

```
#[MapInputName(SnakeCaseMapper::class)]classArtistDataextendsData{publicint$id;publicstring$fullName;publicCarbonImmutable$createdAt;publicCarbonImmutable$updatedAt;
}
```

## # # Relations

Let's create a new model:

```
classSongextendsModel{publicfunctionartist():BelongsTo{return$this->belongsTo(Artist::class);
    }
}
```

Which has the following columns in the database:

- id
- artist\_id
- title

We update our previous model as such:

```
classArtistextendsModel{publicfunctionsongs():HasMany{return$this->hasMany(Song::class);
    }
}
```

We can now create a data object like this:

```
classSongDataextendsData{publicint$id;publicstring$title;
}
```

And update our previous data object like this:

```
classArtistDataextendsData{publicint$id;/**@vararray<SongData>*/publicarray$songs;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

We can now create a data object with the relations like this:

```
$artist=ArtistData::from(Artist::with('songs')->find(1));
```

When you're not loading the relations in advance, null will be returned for the relation.

It is however possible to load the relation on the fly by adding the LoadRelation attribute to the property:

```
classArtistDataextendsData{publicint$id;/**@vararray<SongData>*/#[LoadRelation]publicarray$songs;publicCarbonImmutable$created_at;publicCarbonImmutable$updated_at;
}
```

Now the data object with relations can be created like this:

```
$artist=ArtistData::from(Artist::find(1));
```

We even eager-load the relation for performance, neat!

### # # Be careful with automatic loading of relations

Let's update the SongData class like this:

```
classSongDataextendsData{publicint$id;publicstring$title;#[LoadRelation]publicArtistData$artist;
}
```

When we now create a data object like this:

```
$song=SongData::from(Song::find(1));
```

We'll end up in an infinite loop, since the SongData class will try to load the ArtistData class, which will try to

load the SongData class, and so on.

## # # Missing attributes

When a model is missing attributes and preventAccessingMissingAttributes is enabled for a model the MissingAttributeException won't be thrown when creating a data object with a property that can be null or Optional.

From a request

Injecting property values

Help us improve this page

### On this page

- Casts
- Attributes &amp;amp; Accessors
- Mapping property names
- Relations
- Missing attributes

Laravel beyond CRUD

Check out our course on Laravel development for large apps

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