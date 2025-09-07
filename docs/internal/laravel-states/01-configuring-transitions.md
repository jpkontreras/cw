# Configuring transitions

### On this page

1. Ignoring same state transitions
2. Allow multiple transitions at once
3. Allowing multiple from states
4. Using transitions

Transitions can be used to transition the state of a model from one to another, in a structured and safe way.

You can specify which states are allowed to transition from one to another, and if you want to handle side effects or have more complex transitions, you can also provide custom transition classes.

Transitions are configured in the config method on your state classes.

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowTransition(Pending::class,Paid::class)
            ->allowTransition(Pending::class,Failed::class,PendingToFailed::class);
    }
}
```

In this example we're using both a simple transition, and a custom one. You can also allow all transitions for all registered states. Concrete states extending the abstract state class that are located in the same directory as the abstract state class will be automatically registered:

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowAllTransitions();
    }
}
```

Transitions can then be used like so:

```
$payment->state->transitionTo(Paid::class);
```

This line will only work when a valid transition was configured. If the initial state of $payment already was Paid, a \Spatie\ModelStates\Exceptions\TransitionNotFound will be thrown instead of changing the state.

## # # Ignoring same state transitions

In some cases you may want to handle transition to same state without manually setting allowTransition, you can call ignoreSameState

Please note that the StateChanged event will fire anyway.

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->ignoreSameState()
            ->allowTransition([Created::class,Pending::class],Failed::class,ToFailed::class);
    }
}
```

It also works with IgnoreSameState Attribute

```
#[IgnoreSameState]abstractclassPaymentStateextendsState{//...}
```

## # # Allow multiple transitions at once

A little shorthand allowTransitions can be used to allow multiple transitions at once:

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowTransitions([
                [Pending::class,Paid::class],
                [Pending::class,Failed::class,PendingToFailed::class],
            ]);
    }
}
```

## # # Allowing multiple from states

If you've got multiple states that can transition to the same state, you can define all of them in one allowTransition call:

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowTransition([Created::class,Pending::class],Failed::class,ToFailed::class);
    }
}
```

## # # Using transitions

Transitions can be used by calling the transitionTo method on the state field like so:

```
$payment->state->transitionTo(Paid::class);
```

Listing states

Custom transition classes

Help us improve this page

### On this page

- Ignoring same state transitions
- Allow multiple transitions at once
- Allowing multiple from states
- Using transitions

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