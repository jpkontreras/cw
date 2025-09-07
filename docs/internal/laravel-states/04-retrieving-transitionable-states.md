# Retrieving transitionable states

### On this page

1. Retrieving state instances
2. Retrieving state counts
3. Checking for available transitions
4. Can transition to

An array of transitionable states can be retrieved using the transitionableStates() on the state field.

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Paid::class,Refunded::class);
    }
}
```

```
$transitionableStates=$payment->state->transitionableStates();
```

This will return an array with all transitionable states for the current state, for example Pending:

```
[
    0 =>"paid"]
```

## # # Retrieving state instances

If you need the actual state instances instead of just their string representations, you can use the transitionableStateInstances() method:

```
$stateInstances=$payment->state->transitionableStateInstances();
```

This will return an array of instantiated state objects:

```
[
    0 => Paid {// State instance with model reference}
]
```

### # # Simple example in Blade

This method is particularly useful when you need to access state methods directly. For example, to display available transitions with their properties:

```
@foreach($payment->state->transitionableStateInstances()as$stateInstance)
    <div>
        <span style="color: {{ $stateInstance->color() }}">{{$stateInstance->label() }}</span>
        <i class="{{ $stateInstance->icon() }}"></i>
    </div>
@endforeach
```

With this approach, you can directly call any method defined on your state classes, allowing you to encapsulate UI and business logic within your states:

```
abstractclassPaymentStateextendsState{abstractpublicfunctioncolor():string;abstractpublicfunctionlabel():string;abstractpublicfunctionicon():string;// ...other state methods}classPaidextendsPaymentState{publicfunctioncolor():string{return'#4CAF50'; // green
    }publicfunctionlabel():string{return'Mark as Paid';
    }publicfunctionicon():string{return'check-circle';
    }
}
```

## # # Retrieving state counts

This method tells you how many available transitions exist for the current state.

```
$stateCount=$payment->state->transitionableStatesCount();// 4
```

## # # Checking for available transitions

This method tells you whether there are any available transitions for the current state.

```
$hasTransitions=$payment->state->hasTransitionableStates();// true or false
```

## # # Can transition to

If you want to know whether a state can be transitioned to another one, you can use the canTransitionTo method:

```
$payment->state->canTransitionTo(Paid::class);
```

Dependency injection in transition classes

Transition events

Help us improve this page

### On this page

- Retrieving state instances
- Retrieving state counts
- Checking for available transitions
- Can transition to

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