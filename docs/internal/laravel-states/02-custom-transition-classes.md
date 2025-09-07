# Custom transition classes

If you want your transitions to do more than just changing the state, you can use transition classes.

Imagine transitioning a payment's state from pending to failed, which will also save an error message to the database.

Here's what such a basic transition class might look like.

```
useSpatie\ModelStates\Transition;classPendingToFailedextendsTransition{privatePayment$payment;privatestring$message;publicfunction__construct(Payment$payment,string$message)
    {$this->payment=$payment;$this->message=$message;
    }publicfunctionhandle():Payment{$this->payment->state=newFailed($this->payment);$this->payment->failed_at=now();$this->payment->error_message=$this->message;$this->payment->save();return$this->payment;
    }
}
```

Now the transition should be configured in the model:

```
abstractclassPaymentStateextendsState{// …publicstaticfunctionconfig():StateConfig{returnparent::config()
            ->allowTransition(Pending::class,Failed::class,PendingToFailed::class);
    }
}
```

It can be used like so:

```
$payment->state->transitionTo(Failed::class,'error message');
```

Note: the State::transitionTo method will take as many additional arguments as you'd like,

these arguments will be passed to the transition's constructor.

The first argument in the transition's constructor will always be the model that the transition is performed on.

Another way of handling transitions is by working directly with the transition classes, this allows for better IDE autocompletion, which can be useful to some people. Instead of using transitionTo(), you can use the transition() and pass it a transition class directly.

```
$payment->state->transition(newCreatedToFailed($payment,'error message'));
```

If you're using the approach above, and want to ensure that this transition can only be performed when the payment is in the Created state, you may implement the canTransition() method on the transition class itself.

```
classCreatedToFailedextendsTransition{// …publicfunctioncanTransition():bool{return$this->payment->state->equals(Created::class);
    
    }
}
```

If the check in canTransition() fails, a \Spatie\ModelStates\Exceptions\TransitionNotAllowed will be thrown.

Configuring transitions

Dependency injection in transition classes

Help us improve this page

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