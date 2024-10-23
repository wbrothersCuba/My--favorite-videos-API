MyFavoriteVideos
This app is a backend API generated with Symfony 7.1, for a little project that gives the user the possibility of creating a list of their favorite YouTube videos. The DB is generated in MYSQL so it's needed to adapt the file.env and modify the line to your configurations:

env
 DATABASE_URL="mysql://user@pasword127.0.0.1:3306/api_rest_symfony"
and there is a file named api_rest_symfony.sql with the commands required for restore it. However it can be created from the app itself with the following commands:

Create MYSQL db
symfony console doctrine:database:create
Generate the sql with doctrine
symfony console make:migration
Execute and create the tables
symfony console doctrine:migrations:migrate
The app is configured to work with CORS requests already, so you can make requests using Postman or testing directly in the frontend app. The API uses JWT for authentication so you must generate a token and put it in the headers of the request.

## Endpoints

POST /api_url/user/signup: For registering new users.

POST /api_url/user/signin: Login .

PUT /api_url/user/edit: User settings.

GET /api_url/video/list: Show all the videos of the current user.

POST/api_url/video/new: Add a favorite video.

PUT /api_url/video/edit/{id}: Change data of a video.

GET /api_url/video/detail/{id}: Shows data of one video.

DELETE /api_url/video/remove/{id}: Delete data of a video.
