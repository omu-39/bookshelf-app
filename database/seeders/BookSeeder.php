<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $genres = Genre::all()->keyBy('name');

        $books = [
            ['title' => '吾輩は猫である', 'author' => '夏目漱石', 'isbn' => 9784101010014, 'publication_date' => '1905-01-01', 'genres' => ['小説'], 'description' => '名前を持たない一匹の猫の視点から、明治時代の人間たちの滑稽さや見栄を皮肉たっぷりに描いた風刺小説です。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1'],
            ['title' => '人を動かす', 'author' => 'D・カーネギー', 'isbn' => 9784422100524, 'publication_date' => '1936-10-01', 'genres' => ['ビジネス', '自己啓発'], 'description' => '人間関係の原則を具体的なエピソードで解説した自己啓発の名著。相手を動かすためには批判せず、真摯に関心を持つことが大切だと説きます。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2'],
            ['title' => 'リーダブルコード', 'author' => 'Dustin Boswell', 'isbn' => 9784873115658, 'publication_date' => '2012-06-23', 'genres' => ['技術書'], 'description' => '他の人が読みやすく理解しやすいコードを書くための実践的なテクニックを紹介するプログラマー必読の一冊です。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3'],
            ['title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'isbn' => 9784863940246, 'publication_date' => '2013-08-30', 'genres' => ['ビジネス', '自己啓発'], 'description' => '個人の効果性を高める7つの原則を体系的に解説した自己啓発書。主体的に生き、重要事項を優先し、Win-Winの関係を築く考え方を提示します。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4'],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'isbn' => 9784101010021, 'publication_date' => '1906-04-01', 'genres' => ['小説'], 'description' => '正義感の強い主人公・坊っちゃんが愛媛の中学校に赴任し、腹黒い教師たちと衝突しながら奮闘する痛快な青春小説です。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5'],
            ['title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => 9784309226712, 'publication_date' => '2016-09-08', 'genres' => ['歴史', '科学'], 'description' => '人類がいかにして地球を支配するようになったかを、認知革命・農業革命・科学革命という視点から壮大なスケールで描いた歴史書です。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6'],
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => 9784048930598, 'publication_date' => '2017-12-18', 'genres' => ['技術書'], 'description' => '保守性・可読性の高いコードを書くための原則と実践を解説したソフトウェア開発の定番書。悪いコードと良いコードの具体的な違いを示します。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7'],
            ['title' => '嫌われる勇気', 'author' => '岸見一郎・古賀史健', 'isbn' => 9784478025819, 'publication_date' => '2013-12-13', 'genres' => ['自己啓発'], 'description' => 'アドラー心理学をわかりやすく対話形式で解説した一冊。過去のトラウマに縛られず、今この瞬間を自分らしく生きる勇気を説きます。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8'],
            ['title' => '火花', 'author' => '又吉直樹', 'isbn' => 9784163902302, 'publication_date' => '2015-03-11', 'genres' => ['小説'], 'description' => '売れない若手漫才師が、型破りな先輩芸人との出会いを通じて芸と人生の意味を問い続ける、芥川賞受賞の純文学作品です。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9'],
            ['title' => 'FACTFULNESS', 'author' => 'ハンス・ロスリング', 'isbn' => 9784822289607, 'publication_date' => '2019-01-11', 'genres' => ['ビジネス', '科学'], 'description' => 'データに基づく正しい世界の見方を10の本能から解説した書。先入観やメディアに惑わされず、事実に基づいて世界を認識する重要性を説きます。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10'],
            ['title' => 'コンテナ物語', 'author' => 'マルク・レビンソン', 'isbn' => 9784822251468, 'publication_date' => '2007-01-18', 'genres' => ['ビジネス', '歴史'], 'description' => '鉄のコンテナが世界の物流を劇的に変え、グローバル経済を生み出した歴史を描いたノンフィクション。技術革新と経済変革の関係を解説します。', 'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11'],
        ];

        foreach ($books as $book) {
            $book = Book::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'isbn' => $book['isbn'],
                    'publication_date' => $book['publication_date'],
                    'description' => $book['description'],
                    'image_url' => $book['image_url'],
                ]
            );

            $genreIds = collect($book['genres'])->map(fn ($name) => $genres[$name]->id)->toArray();
            $book->genres()->sync($genreIds);
        }
    }
}