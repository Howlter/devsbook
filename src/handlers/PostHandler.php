<?php
namespace src\handlers;

use \src\models\Post;
use \src\models\User;
use \src\models\UserRelation;

class PostHandler {

    public static function addPost($idUser, $type, $body){
        $body = trim($body);
        if(!empty($idUser) && !empty($body)){
            Post::insert([
                'id_user' => $idUser,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'body' => $body
            ])->execute();
        }
    }

    public static function getHomeFeed($idUser, $page) {
        $perPage = 2;
        // 1. Pegar lista de usúarios que está seguindo
        $userList = UserRelation::select()->where('user_from', $idUser)->get();
        $users = [];
        foreach($userList as $userItem){
            $users[] = $userItem['user_to'];
        }
        $users[] = $idUser;
        
        // 2. Pegar posts ordenados pela data
        $postList = Post::select()
            ->where('id_user', 'in', $users)
            ->orderBy('created_at', 'desc')
            ->page($page, $perPage)
        ->get();

        $total = Post::select()
        ->where('id_user', 'in', $users)
        ->count();
        $pageCount = ceil($total / $perPage);


        //3. Transformar em objetos do models
        $posts = [];
        foreach($postList as $postItem){
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type'];
            $newPost->created_at = $postItem['created_at'];
            $newPost->body = $postItem['body'];
            $newPost->mine = false;

            if($postItem['id_user'] == $idUser){
                $newPost->mine = true;
            }
            
            //4. Preencher as informações do post

            $newUser = User::select()->where('id', $postItem['id_user'])->one();
            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];

            //TO DO 4.1 Preencher informações de like

            $newPost->likeCount = 0;
            $newPost->liked = false;

            //TO DO 4.2 Preencher informações de comments

            $newPost->comments = [];

            $posts[] = $newPost;
        }
                    //5. Retornar o resultado
                    return [
                        'posts'=>$posts,
                        'pageCount'=> $pageCount,
                        'currentPage'=> $page
                    ];
    }
}