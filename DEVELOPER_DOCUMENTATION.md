# Developer documentation for topics
## Content
1. Different sections of the application
2. UI / Frontend
3. Backend
4. Background services
5. Logging
6. Caching
7. Asynchronous server requests (AJAX)

## 1. Different sections of the application
The application has different sections of code. All the important code is located in the `app/` directory.

### 1.1. Authenticators
Authenticators are located in `app/authenticators`. The only used authenticator is the `UserAuthenticator` that allows users to login and also to perform certain tasks, where authentication of the user is needed.

The `UserAuthenticator` has several methods. E.g. `loginUser()` is used in the login form, when user tries to login. It checks if the username and password entered are correct and equal to information saved in the database. Documentation for other methods can be found in the class itself.

### 1.2. Authorizators
Authorizators are located in `app/authorizators`. Their job is to check if user is authorized to perform or see certain things.

Currently three different authorizators are used - `ActionAuthorizator`, `SidebarAuthorizator` and `VisibilityAuthorizator`. All these three authorizators extend an abstract `AAuthorizator` that contains common methods.

`ActionAuthorizator` is responsible for checking if user is allowed to perform certain actions - e.g. delete posts, create polls, manage topic users, etc.

`SidebarAuthorizator` is responsible for checking if user is allowed to view different sections of sidebar that is used in the management section of the application.

`VisibilityAuthorizator` is responsible for checking if user is allowed to view deleted posts, topics and private topics.

### 1.3. Components