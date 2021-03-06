<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommentsController extends Controller
{
    /**
     * CommentsController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\CommentsRequest $request
     * @param \App\Article $article
     * @return \Illuminate\Http\Response
     */
    public function store(\App\Http\Requests\CommentsRequest $request, \App\Article $article)
    {
        $comment = $article->comments()->create(array_merge(
            $request->all(),
            ['user_id' => $request->user()->id]
        ));

        event(new \App\Events\ModelChanged(['articles']));
        event(new \App\Events\CommentsEvent($comment));

        return $this->respondCreated($article, $comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\CommentsRequest $request
     * @param \App\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function update(\App\Http\Requests\CommentsRequest $request, \App\Comment $comment)
    {
        $comment->update($request->all());

        event(new \App\Events\ModelChanged(['articles']));

        return $this->respondUpdated($comment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(\App\Comment $comment)
    {
        if ($comment->replies->count() > 0) {
            $comment->delete();
        } else {
            $comment->votes()->delete();
            $comment->forceDelete();
        }

        event(new \App\Events\ModelChanged(['articles']));

        return response()->json([], 204);
    }

    /**
     * Vote up or down for the given comment.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Comment $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function vote(Request $request, \App\Comment $comment)
    {
        $this->validate($request, [
            'vote' => 'required|in:up,down',
        ]);

        if ($comment->votes()->whereUserId($request->user()->id)->exists()) {
            return response()->json(['error' => 'already_voted'], 409);
        }

        $up = $request->input('vote') == 'up' ? true : false;

        $comment->votes()->create([
            'user_id'  => $request->user()->id,
            'up'       => $up,
            'down'     => ! $up,
            'voted_at' => \Carbon\Carbon::now()->toDateTimeString(),
        ]);
        
        return response()->json([
            'voted' => $request->input('vote'),
            'value' => $comment->votes()->sum($request->input('vote')),
        ]);
    }

    /* Response Methods */

    /**
     * @param \App\Article $article
     * @param $comment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function respondCreated(\App\Article $article, $comment)
    {
        flash()->success(trans('forum.comments.success_writing'));

        return redirect(route('articles.show', $article->id) . '#comment_' . $comment->id);
    }

    /**
     * @param \App\Comment $comment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function respondUpdated(\App\Comment $comment)
    {
        flash()->success(trans('forum.comments.success_updating'));

        return redirect(route('articles.show', $comment->commentable->id) . '#comment_' . $comment->id);
    }
}
