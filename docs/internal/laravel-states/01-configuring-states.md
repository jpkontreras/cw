# Configuring states

### On this page

1. Manually registering states
2. Registering states from custom directories
3. Configuring states using attributes
4. Improved typehinting

This package provides a HasStates trait which you can use in whatever model you want state support in. Within your codebase, each state is represented by a class, and will be serialised to the database by this package behind the scenes.

```
useSpatie\ModelStates\HasStates;classPaymentextendsModel{useHasStates;// …}
```

A model can have as many state fields as you want, and you're allowed to call them whatever you want. Just make sure every state has a corresponding database string field.

```
Schema::table('payments',function(Blueprint$table) {$table->string('state');
});
```

Each state field should be represented by a class, which itself extends an abstract class you also must provide. An example would be PaymentState, having three concrete implementations: Pending, Paid and Failed.

```
useSpatie\ModelStates\State;/**
 *@extendsState<\App\Models\Payment>
 */abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;
}
```

```
classPaidextendsPaymentState{publicfunctioncolor():string{return'green';
    }
}
```

There might be some cases where this abstract class will simply be empty, still it's important to provide it, as type validation will be done using it.

To link the Payment::$state field and the PaymentState class together, you should list it as a cast:

```
classPaymentextendsModel{// …protected$casts= ['state'=>PaymentState::class,
    ];
}
```

States can be configured to have a default value and to register transitions. This is done by implementing the config method in your abstract state classes:

```
useSpatie\ModelStates\State;useSpatie\ModelStates\StateConfig;abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Pending::class,Failed::class)
        ;
    }
}
```

## # # Manually registering states

If you want to place your concrete state implementations in a different directory, you may do so and register them manually:

```
useSpatie\ModelStates\State;useSpatie\ModelStates\StateConfig;useYour\Concrete\State\Class\Cancelled;// this may be wherever you wantuseYour\Concrete\State\Class\ExampleOne;useYour\Concrete\State\Class\ExampleTwo;abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Pending::class,Failed::class)
            ->registerState(Cancelled::class)
            ->registerState([ExampleOne::class,ExampleTwo::class])
        ;
    }
}
```

## # # Registering states from custom directories

If you want to register all state classes from one or more directories, you can use the registerStatesFromDirectory method. This is useful if you organize your state classes in multiple folders and want to avoid registering each one manually.

```
useSpatie\ModelStates\State;useSpatie\ModelStates\StateConfig;abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Pending::class,Failed::class)
            ->registerStatesFromDirectory(app_path('States/Payment'))
            ->registerStatesFromDirectory(__DIR__.'/States',__DIR__.'/MoreStates',// add as many directories as you need);
    }
}
```

This will automatically discover and register all state classes in the given directory that extend your base state class.

### # # Registering custom StateChanged event

By default, when a state is changed, the StateChanged event is fired. If you want to use a custom event, you can register it in the config method:

```
useSpatie\ModelStates\State;useSpatie\ModelStates\StateConfig;useYour\Concrete\State\Event\CustomStateChanged;abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->stateChangedEvent(CustomStateChanged::class)
        ;
    }
}
```

## # # Configuring states using attributes

If you're using PHP 8 or higher, you can also configure your state using attributes:

```
useSpatie\ModelStates\Attributes\AllowTransition;useSpatie\ModelStates\Attributes\RegisterState;useSpatie\ModelStates\State;

#[AllowTransition(Pending::class,Paid::class),AllowTransition(Pending::class,Failed::class),DefaultState(Pending::class),RegisterState(Cancelled::class),RegisterState([ExampleOne::class,ExampleTwo::class]),
]abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;
}
```

Next up, we'll take a moment to discuss how state classes are serialized to the database.

## # # Improved typehinting

Optionally, for improved type-hinting, the package also provides a HasStatesContract interface.

This way you don't have to worry about whether a state is in its textual form or not: you're always working with state objects.

```
useSpatie\ModelStates\HasStates;useSpatie\ModelStates\HasStatesContract;classPaymentextendsModelimplementsHasStatesContract{useHasStates;// …}
```

About us

Serializing states

Help us improve this page

### On this page

- Manually registering states
- Registering states from custom directories
- Configuring states using attributes
- Improved typehinting

Mailcoach

Check out our full-featured (self-hosted) email marketing solution

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