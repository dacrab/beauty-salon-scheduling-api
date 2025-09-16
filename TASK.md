# Beauty Salon API Coding Challenge

## Introduction

Welcome to our coding challenge! In this assignment, you'll build a basic appointment scheduling API for a beauty salon. The challenge is structured with a core Main Task and some Optional Additions (including a very advanced scenario for those who love extra challenges). Focus on completing the Main Task first – a working implementation of the main requirements is required to advance to the final interview stage. The optional tasks are truly optional; you won't be penalized for skipping them. They're there in case you want to showcase more skills or have fun exploring extra features. Don't be intimidated by the optional parts – a solid solution for the main task is what we care about most!

Throughout this document, we'll outline what we expect for the main task and describe the optional enhancements. We'll also provide some hints and examples to guide you, but all design choices are up to you. Feel free to use different approaches than the examples given – they are just suggestions, not requirements.

## Main Task: Beauty Salon API

**Objective:** Set up a Laravel application and implement a RESTful API for a beauty salon scheduling system with three core endpoints. The API will allow listing available appointment slots, booking an appointment, and canceling an appointment.

### Key Requirements:

#### Laravel Setup & Hosting
Create a new Laravel application (you can use the latest version of Laravel). Host it on a local web server environment of your choice (for example, you might use Nginx or Apache on localhost, or run it in a Docker container). Do not use `php artisan serve` for this – we want to see it running on a "real" web server (to simulate a production-like environment). The exact setup is up to you (Nginx/Apache config, Docker compose, etc.), but provide instructions in the README so we can run it easily.

*Hint: If you're comfortable with Docker, you could, for instance, use the official Laravel/PHP Docker images and maybe a MySQL container. Or you could set up Laravel Valet/Homestead or a simple Nginx + PHP-FPM on your machine. Choose whatever you're most familiar with.*

#### Database & Models
Design an appropriate database schema to support scheduling:
- You will need to represent **Specialists** (the staff members who perform services).
- You will need to represent **Services** (e.g. haircut, hairstyling, manicure) with a duration for each.
- You will need an **Appointments** model (to track booked appointments, linking a specialist, a service, a date/time, and possibly a user or client name if you choose).
- Feel free to add any other models or fields that make sense. For example, you might want a User model if you implement authentication for clients, but since this is a basic scenario, it's fine if you assume a single user context or just handle appointments without distinct user accounts.

#### Salon Scenario Setup
For the sake of this challenge, assume the salon has 3 specialists (let's call them A, B, and C) and 3 types of services:
- **Specialist A** can do "haircut" and "hairstyling".
- **Specialist B** can do "haircut" and "manicure".
- **Specialist C** can do "hairstyling" and "manicure".
- Each service has a specific duration. For example, you can assume:
  - **Haircut** – 50 minutes
  - **Hairstyling** – 70 minutes
  - **Manicure** – 25 minutes

*(These durations are just examples; you can choose different reasonable durations for each service if you like. The key is that durations differ, affecting scheduling.)*

#### Working Hours & Time Slots
Define an assumed working schedule for the salon (e.g., 9:00 to 18:00 – you decide). All specialists work during these full store hours. The "List available slots" endpoint will need to compute what appointment start times are available for a given service and for a given specialist within those working hours.

*Hint: for simplicity you can calculate slots that start every 30 minutes - your choice - for example the first available slot is at 9:00 and the next one starts at 9:30*

- When listing slots, consider existing appointments so you don't offer a slot that's already booked.
- Also consider the service duration – e.g., if a service is 50 minutes and a specialist has an appointment starting at 9:00 that lasts 50 min, the specialist won't be free until 9:50, so the next available slot for them should be at 10:00.
- *Hint: One way to approach slots is to break the day into small time increments (say, 5-minute blocks) and calculate openings from there. For example, you could quantize time into 5-min intervals to help align start times nicely. This is not required, but it's a useful trick to make scheduling easier. You can also assume no breaks between appointments for simplicity.*

#### API Endpoints
Implement the following endpoints (exact URL paths are up to you, e.g., you might use `/api/slots`, `/api/book`, etc.):

1. **List Available Slots** – Returns all available appointment time slots for a given service and a given specialist. The one making the request (e.g via postman or curl, etc) should be able to specify which service they want to check availability for as well as the specific specialist they want. The response should list time slots during the working hours where that service can be booked. The format is up to you – e.g., you could return a list of `{specialist, start_time, end_time}` pairs that are open.

2. **Book an Appointment** – Allows booking an appointment for a given specialist, service, and time slot. The client will provide the necessary details (which service, which specialist and the desired start time). The endpoint should create an appointment if the slot is still available, and return a confirmation (or an error if that slot is no longer free). After a successful booking, that slot should no longer appear as available for future queries.

3. **Cancel an Appointment** – Allows canceling an existing appointment. The client could specify the appointment ID. This endpoint should delete or mark the appointment as canceled, freeing up the slot again for future bookings.

#### Basic Auth
Secure the API with basic authentication using a Bearer token in the header. In practice, this could be done with Laravel Passport, Sanctum, or even a simple hard-coded token for the sake of the challenge. The goal is to ensure that the endpoints only work if a valid token is provided. For example, you might seed a sample API token for a test user or use Laravel's built-in token abilities. Document in the README how to use the token (e.g., "set header Authorization: Bearer <your_token>").

*Note: We're not expecting a full OAuth2 or user registration flow. It's perfectly fine to have a single token that you validate (for instance, using Laravel middleware) just to simulate protected endpoints.*

#### Seeder Data
Set up proper seeders for your database models to initialize the application with some sample data:
- Create the 3 specialists (A, B, C with their service capabilities). You might have a pivot table or some relation between specialists and services they offer.
- Create the 3 services with their durations.
- Create at least 3 random appointments per specialist (so at least 9 total) at random times (within working hours) for various services that that specialist can perform. This will allow us to test that your "list slots" logic correctly skips over already booked times. Make sure these seeded appointments don't all overlap in the same slot – spread them out a bit.
- Ensure the seed data is realistic (e.g., appointments fall within working hours, and no specialist has overlapping appointments in the seed). This seeded data acts as initial content of the schedule.

#### Design Freedom
You are free to implement the above however you see fit. For example, how you calculate available slots, how you structure your database, and how you implement the API endpoints is entirely your choice. We will look at the correctness (does it meet requirements?) and code quality, not whether you followed one particular method. We've given some hints (like using 5-minute increments or example durations), but you can solve it in any way that works. Feel free to add any other improvements to the main task if you think they are necessary or beneficial – just make sure to document any assumptions or decisions in your README.

#### README & Repository
Once you have completed the coding, host your code in a private GitHub repository and share access with our team (a.spyratos@yoltlabs.com, p.anagnostopoulos@yoltlabs.com). Include a README.md that clearly explains:
- The steps to set up and run the project on our end. (This should cover installing dependencies, setting up the database, running migrations and seeders, how to start the web server or Docker container, etc.)
- Any special instructions for testing the API (e.g., what base URL and endpoints to use, how to include the bearer token, and maybe example requests).
- Any design decisions, assumptions, or shortcuts you took. For example, if you assumed the store hours are X to Y, mention that. If you didn't implement something that was mentioned, you can note that too.
- Basically, the README should allow us to understand and run your project without issues.

By completing the above main task, you will have demonstrated the core skills we're looking for. A complete, working Main Task is mandatory to move forward to the final phase of the interview process. Make sure it runs and meets the requirements. If something is not fully done, it's better to explain in the README what's missing or how you'd approach it, rather than leave us guessing, as these might cover the "gap" in the code, giving you the green light for the final interview.

## Optional Additions (Bonus Features)

Once you have the main API working, you may implement optional features (can be anything you like, or something from the examples below) to earn bonus points and demonstrate extra skills. This step is 100% optional – you can still have a successful interview outcome without doing anything extra, so don't worry if you skip these. Only move forward if you have time and want to showcase more.

Some ideas for optional additions:

### Unit Tests
Implement a test for booking an appointment. E.g test that a new appointment is created or that canceling an appointment works as expected. Using Laravel's testing framework (PHPUnit) is fine.

### Email Notifications (Reminders)
Set up an email feature to send a reminder to the user who booked an appointment, 3 hours before the appointment time. You might use Laravel's built-in Notification or Mail features to implement this. Don't worry about actually sending real emails – using the log mail driver or just writing to a file is fine. We're interested in the code structure, not setting up a mail server.

For any email functionality, feel free to use solutions like Laravel's scheduler/queue, or simply simulate the timing (e.g., a console command that when run will dispatch the "reminder emails" for any appointments 3 hours out). Document how we can see this feature in action (maybe it writes to log or displays a message, etc., since we might not actually wait around for 3 hours in real time).

*Note: These optional features are a chance to show familiarity with Laravel's testing and notification tools, but it's okay if you're not as comfortable – skip them if unsure. A well-done main task is far more important to us than half-done bonus features.*

## Advanced Challenge: Multi-Service Booking (Extremely optional)

This section is for those who love a big challenge. Please only attempt this if you have the time and want to explore a complex scenario. We do NOT expect MOST candidates to complete this. It's there to see how you might approach a tougher problem and to give you something interesting to think about if you finish everything else quickly. Going for this stage does not count as an indicator that "someone wants the position more than someone else". Given that, please proceed only if you truly feel like it and you really wish to spend time on such a challenge.

**Scenario:** Extend the "List Available Slots" functionality to handle a user who wants to book multiple services back-to-back in one visit. In other words, the client might request two or more different services in one appointment session (for example, a haircut followed by a manicure, back-to-back).

We've broken this advanced request into three levels of difficulty. You can choose to implement the medium level, or hard, or ultra-hard, depending on your ambition. You can also skip this entirely, of course.

### 1. Medium Level Assumptions
The client can request exactly 2 services, to be done back-to-back (one immediately after the other). The client must specify which specialist for each service, and you assume the services will be performed in the order they are provided in the request. You need to find all available time slots where Specialist X is free to do Service 1, and then immediately after, Specialist Y is free to do Service 2. (X and Y could be the same person if the same specialist is specified and offers both services, or different people.)

**Example:** "I want a Haircut (with Specialist B) and then a Manicure (with Specialist C) back-to-back." Your system should be able to return all available "superSlots" such that B is free for 50min from that time for a haircut and, immediately after, C is free for 25min for the manicure. Remember that If B or C have conflicts that prevent this sequence, find the next possible slot.

This is essentially a search for two consecutive free slots (the second starting right when the first ends). You can assume only two services and that the order is fixed and given.

### 2. Hard Level Assumptions
The client can request 2 services (like above), but they have the option to say either a specific specialist or "Any specialist" for each service. This means your logic has to consider multiple specialists' availability for each part. For simplicity, you could still assume 2 services and the order given.

**Example:** "I want a Haircut (with any specialist who can do it) and then a Hairstyling (specifically with Specialist A) back-to-back." Now your system might need to first find which specialist can do the haircut at a time that Specialist A is free right after for 70 minutes to do the hairstyling.

This adds complexity because for the "any specialist" part you have to try all possibilities.

### 3. Ultra-Hard Level Assumptions
The client can request any number of services (2 or more). They can specify a specialist for some or say "any" for others. And the services could theoretically be done in any order. Essentially, this becomes a complex scheduling problem where you need to try different permutations and combinations to find a continuous block where each service can happen one after the other.

This level is extremely challenging. It involves checking multiple specialists' schedules, possibly generating all permutations of service order, and finding a chain of availability. It's like an "optimized booking" problem.

We absolutely do not expect a full solution here; if you attempt it, even a partially working approach or pseudocode in your README explaining how you would tackle it is great.

### Hints for Multi-Service Booking
If you do attempt any of the above multi-service scenarios, here are a few tips to guide you:

- Think about time slots in small increments (like 5-minute ticks, as mentioned before). This can simplify checking for openings, since you can represent a schedule as a grid of 5-min blocks (true/false for free/busy).
- For all scenarios, a brute-force search might be fine: try every possible start time on a given day and see if it fits both services.
- For the ultra scenario, you might need to use a backtracking algorithm or recursion, or utilize disk space via new models in the database to save complexity: assign the first service to some specialist at some time, then move to the next, etc., and backtrack if you hit a conflict. This is complex, so even outlining this logic is an accomplishment.
- Don't worry about optimizing too much for ultra-hard; focus on correctness (if you tackle it at all). It's okay if the solution is slow or not perfectly optimized, given the small scale (3 specialists, a day's worth of slots).
- Clearly state any assumptions you make. For example, you might say "For multi-service booking, I assumed the request order is the service order and limited to 2 services" – that's perfectly fine.

Again, implementing the multi-service functionality is entirely optional. We're interested in how you think about the problem, so any attempt (or even a discussion in the README of how you would do it) can be valuable.

## Final Notes and What We're Looking For

### Completion of Main Task
To reiterate, advancing to the final interview round requires a complete, working Main Task implementation. Ensure that your core API (slots listing, booking, canceling) works correctly and that we can run it following your instructions. It's better to have a solid main solution than an incomplete attempt at every optional feature. Quality over quantity!

### Optional Features
These can impress us but they won't make up for a lacking main task. It's absolutely fine if you skip them. We want you to feel comfortable showcasing what you can do, not stressed about ticking every box. If you do implement any, it will be a nice bonus, and we'll definitely take note of it – just remember to mention in your README or somewhere which optional features you added so we don't miss them when reviewing.

### Code and Design
We intentionally leave the design choices to you. Use whatever architecture and coding style you feel is best. We'll be looking at how you structure your code, clarity, and maintainability. For example, using Laravel best practices (routing, controllers, services, etc.) is encouraged. If you decide to use packages or tools (for example, maybe a package for scheduling or an external library), that's fine – just note it in the README. We provided example ideas (like durations, working hours, etc.), but you can choose differently as long as the logic is consistent.

### Documentation
A friendly reminder to make sure your documentation (README) is clear. If there are any setup quirks or if we need to create an .env file with specific values, let us know. If we have to run special commands (like to run a scheduler or queue worker to see your email feature), document that too. The easier you make it for us to run and test your app, the happier we'll be 😊.

### Use of AI Tools
You are more than welcome to use AI assistants or any other tools while working on this project! This challenge is meant to simulate a real-work scenario, and in real life developers use Google, Stack Overflow, ChatGPT, etc. to help them. If you choose to ask a chatbot (like ChatGPT or others) for help, we'd actually love to see that. Ideally, do it in a single conversation thread (so it's easy to review), and at the end, you can share that conversation with us (for example, if it's ChatGPT, you could share the chat link or export it). Don't be afraid or ashamed of this – we do not consider it cheating. On the contrary, showing that you can leverage AI tools effectively is a plus in our book. We're interested in how you use such tools – the prompts you ask, how you incorporate suggestions, etc. It gives us insight into your problem-solving process.

*(Of course, the code you submit should ultimately be understood and maintained by you. If an AI gives you a chunk of code, make sure you review and understand it before including it. Using AI is not mandatory at all – it's just an option. And if you do use it, honesty is the best policy. We'd prefer you share the usage than try to hide it.)*

### Support and Questions
If anything in the task description is unclear or if you have questions, feel free to reach out to us for clarification (just as you would in a real job if requirements were unclear). We're here to help you succeed.

Good luck, and have fun coding! We look forward to seeing your solution. Remember: focus on the basics first, and feel free to get creative once the core is done. We're excited to see your approach. Happy coding! 🎉
