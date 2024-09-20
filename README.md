MyFavoriteVideos
This app is a backend API generated with symfony 7.1, for a little project that gives to the user the posibility of create a list of their favorite youtube videos. The DB is generated in MYSQL so its needed to adapt the file.env and modify the line to your own configurations:

env
 DATABASE_URL="mysql://user@pasword127.0.0.1:3306/api_rest_symfony"
and there is a file named api_rest_symfony.sql with the commands required for restore it. However it can be created from the app itself with the followings commands:

Create MYSQL db
symfony console doctrine:database:create
Generate the sql with doctrine
symfony console make:migration
Execute and create the tables
symfony console doctrine:migrations:migrate
The app is configured to work with CORS requests already, so you can make request using postman or testing directly in the frontend app. The API use JWT for authentication so you must generate a token and put it in the headers of the request.

Endpoints
POST /api_url/user/signup: For register new user.

POST /api_url/user/signin: Login .

PUT /api_url/user/edit: User settings.

GET /api_url/video/list: Show all the videos of the current user.

POST/api_url/video/new: Add a favorite video.

PUT /api_url/video/edit/{id}: Change data of a video.

GET /api_url/video/detail/{id}: Shows data of one video.

DELETE /api_url/video/remove/{id}: Delete data of a video.