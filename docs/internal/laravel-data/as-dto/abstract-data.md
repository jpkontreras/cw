# Abstract Data

It is possible to create an abstract data class with subclasses extending it:

```
abstractclassPersonextendsData{publicstring$name;
}classSingerextendsPerson{publicfunction__construct(publicstring$voice,) {}
}classMusicianextendsPerson{publicfunction__construct(publicstring$instrument,) {}
}
```

It is perfectly possible now to create individual instances as follows:

```
Singer::from(['name'=>'Rick Astley','voice'=>'tenor']);Musician::from(['name'=>'Rick Astley','instrument'=>'guitar']);
```

But what if you want to use this abstract type in another data class like this:

```
classContractextendsData{publicstring$label;publicPerson$artist;
}
```

While the following may both be valid:

```
Contract::from(['label'=>'PIAS','artist'=> ['name'=>'Rick Astley','voice'=>'tenor']]);Contract::from(['label'=>'PIAS','artist'=> ['name'=>'Rick Astley','instrument'=>'guitar']]);
```

The package can't decide which subclass to construct for the property.

You can implement the PropertyMorphableData interface on the abstract class to solve this. This interface adds a morph method that will be used to determine which subclass to use. The morph method receives an array of properties limited to properties tagged by a  PropertyForMorph attribute.

```
useSpatie\LaravelData\Attributes\PropertyForMorph;useSpatie\LaravelData\Contracts\PropertyMorphableData;abstractclassPersonextendsDataimplementsPropertyMorphableData{#[PropertyForMorph]publicstring$type;publicstring$name;publicstaticfunctionmorph(array$properties):?string{returnmatch($properties['type']) {'singer'=>Singer::class,'musician'=>Musician::class,default=>null};
    }
}
```

The example above will work by adding this code, and the correct Data class will be constructed.

Since the morph functionality needs to run early within the data construction process, it bypasses the normal flow of constructing data objects so there are a few limitations:

- it is only allowed to use properties typed as string, int, or BackedEnum(int or string)
- When a property is typed as an enum, the value passed to the morph method will be an enum
- it can be that the value of a property within the morph method is null or a different type than expected since it runs before validation
- properties with mapped property names are still supported

It is also possible to use abstract data classes as collections as such:

```
classBandextendsData{publicstring$name;/**@vararray<Person>*/publicarray$members;
}
```

Collections

Casts

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