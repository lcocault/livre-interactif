<?php

namespace App\Controller;

use App\Model\Book;
use App\Model\Character;

class HomeController extends Controller
{
    public function index(): void
    {
        $books      = Book::allPublished();
        $characters = $this->userId() ? Character::findByUser($this->userId()) : [];
        $flash      = $this->getFlash();

        $this->render('home', compact('books', 'characters', 'flash'));
    }
}
