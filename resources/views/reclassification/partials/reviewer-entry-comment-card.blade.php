@php
    $currentReviewerRole = strtolower((string) ($reviewerRole ?? ''));
    $commentAuthorRole = strtolower((string) ($comment->author?->role ?? ''));
    $canManageThisThread = $currentReviewerRole !== '' && $commentAuthorRole === $currentReviewerRole;
    $visibilityClass = $comment->visibility === 'faculty_visible'
        ? 'bg-green-50 text-green-700 border-green-200'
        : 'bg-gray-50 text-gray-600 border-gray-200';
    $visibilityLabel = $comment->visibility === 'faculty_visible'
        ? 'Visible to faculty'
        : 'Internal';
    $commentActionType = (string) ($comment->action_type ?? 'requires_action');
    if ($commentActionType === 'info') {
        $commentActionClass = 'bg-slate-50 text-slate-700 border-slate-200';
        $commentActionLabel = 'No action required';
    } else {
        $commentActionClass = match((string) ($comment->status ?? 'open')) {
            'resolved' => 'bg-green-50 text-green-700 border-green-200',
            'addressed' => 'bg-blue-50 text-blue-700 border-blue-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
        $commentActionLabel = match((string) ($comment->status ?? 'open')) {
            'resolved' => 'Resolved by reviewer',
            'addressed' => 'Addressed by faculty',
            default => 'Action required',
        };
    }
    $status = $comment->status ?? 'open';
    $replies = $rowComments
        ->where('parent_id', $comment->id)
        ->sortBy('created_at')
        ->values();
    $initialEditVisibility = (string) ($comment->visibility ?? 'faculty_visible');
    $initialEditActionType = (string) ($comment->action_type ?? ($initialEditVisibility === 'internal' ? 'info' : 'requires_action'));
    $canEditThisThread = $canManageThisThread
        && (string) ($comment->status ?? 'open') === 'open'
        && $replies->isEmpty();
@endphp
<div class="rounded-lg border border-gray-200 bg-white p-2.5 text-left space-y-1"
     x-data="{
        editOpen: false,
        editBody: @js(trim((string) ($comment->body ?? ''))),
        editVisibility: @js($initialEditVisibility),
        editActionType: @js($initialEditActionType),
        openEdit() {
            this.editOpen = true;
        },
        canSaveEdit() {
            const hasBody = String(this.editBody || '').trim() !== '';
            const visibility = String(this.editVisibility || '').trim();
            if (!hasBody || visibility === '') return false;
            if (visibility === 'faculty_visible') {
                return String(this.editActionType || '').trim() !== '';
            }
            return true;
        },
        resetEdit() {
            this.editBody = @js(trim((string) ($comment->body ?? '')));
            this.editVisibility = @js($initialEditVisibility);
            this.editActionType = @js($initialEditActionType);
            this.editOpen = false;
        }
     }"
     @click.window="if (editOpen && !$refs.editForm?.contains($event.target) && !$refs.editToggle?.contains($event.target)) resetEdit()">
    <div class="flex items-center justify-between gap-2">
        <div class="text-[11px] font-semibold text-gray-800">
            {{ $comment->author?->name ?? 'Reviewer' }} - {{ optional($comment->created_at)->format('M d, Y g:i A') }}
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $visibilityClass }}">
                {{ $visibilityLabel }}
            </span>
            @if($comment->visibility === 'faculty_visible')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $commentActionClass }}">
                    {{ $commentActionLabel }}
                </span>
            @endif
        </div>
    </div>
    <div class="text-[10px] font-semibold text-gray-500">Reviewer Comment:</div>
    <div class="flex items-start justify-between gap-2" x-show="!editOpen">
        <div class="min-w-0 text-[13px] leading-5 text-gray-800 break-words">{{ $comment->body }}</div>
        <div class="shrink-0 flex items-center gap-1.5">
            @if($canEditThisThread)
                <button type="button"
                        x-ref="editToggle"
                        @click="openEdit()"
                        class="px-2 py-0.5 rounded border border-blue-200 bg-blue-50 text-[10px] font-semibold text-blue-700 hover:bg-blue-100">
                    Edit
                </button>
            @endif
            @if($canManageThisThread && ($comment->status ?? 'open') !== 'resolved')
                <form method="POST"
                      action="{{ route('reclassification.row-comments.destroy', $comment) }}"
                      data-async-action
                      data-async-refresh-target="#reviewer-content"
                      data-loading-text="Removing..."
                      data-loading-message="Removing comment..."
                      data-confirm-modal="1"
                      data-confirm-title="Remove Comment"
                      data-confirm-confirm-text="Remove"
                      data-confirm-cancel-text="Cancel"
                      data-confirm-destructive="1"
                      data-confirm="Remove this comment thread?">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-2 py-0.5 rounded border border-red-200 bg-red-50 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                        Remove
                    </button>
                </form>
            @endif
        </div>
    </div>
    @if($canEditThisThread)
        <div x-show="editOpen"
             x-ref="editForm"
             x-cloak
             class="mt-1 rounded-lg border border-gray-200 bg-gray-50 p-2.5">
            <form method="POST"
                  action="{{ route('reclassification.row-comments.update', $comment) }}"
                  data-async-action
                  data-async-refresh-target="#reviewer-content"
                  data-loading-text="Saving..."
                  data-loading-message="Updating comment..."
                  class="grid grid-cols-1 gap-2 md:grid-cols-7 md:items-end">
                @csrf
                <div class="md:col-span-3">
                    <label class="text-xs text-gray-600">Edit comment</label>
                    <textarea name="body"
                              rows="2"
                              required
                              x-model="editBody"
                              class="mt-1 w-full rounded-lg border-gray-300 text-xs"
                              placeholder="Edit comment..."></textarea>
                </div>
                <div class="md:col-span-1">
                    <label class="text-xs text-gray-600">Visibility</label>
                    <div class="mt-1 space-y-1.5 text-xs">
                        <label class="flex items-center gap-2 text-gray-700">
                            <input type="radio"
                                   name="visibility"
                                   value="faculty_visible"
                                   x-model="editVisibility"
                                   required
                                   class="border-gray-300 text-bu focus:ring-bu">
                            <span>Visible to faculty</span>
                        </label>
                        <label class="flex items-center gap-2 text-gray-700">
                            <input type="radio"
                                   name="visibility"
                                   value="internal"
                                   x-model="editVisibility"
                                   class="border-gray-300 text-bu focus:ring-bu">
                            <span>Internal</span>
                        </label>
                    </div>
                </div>
                <template x-if="editVisibility === 'faculty_visible'">
                    <div class="md:col-span-1">
                        <label class="text-xs text-gray-600">Type</label>
                        <div class="mt-1 space-y-1.5 text-xs">
                            <label class="flex items-center gap-2 text-gray-700">
                                <input type="radio"
                                       name="action_type"
                                       value="requires_action"
                                       x-model="editActionType"
                                       required
                                       class="border-gray-300 text-bu focus:ring-bu">
                                <span>Action required</span>
                            </label>
                            <label class="flex items-center gap-2 text-gray-700">
                                <input type="radio"
                                       name="action_type"
                                       value="info"
                                       x-model="editActionType"
                                       class="border-gray-300 text-bu focus:ring-bu">
                                <span>FYI</span>
                            </label>
                        </div>
                    </div>
                </template>
                <template x-if="editVisibility === 'internal'">
                    <input type="hidden" name="action_type" value="info">
                </template>
                <div class="mt-1 flex justify-end gap-2 md:col-span-2 md:col-start-6 md:mt-0 md:self-end md:justify-end">
                    <button type="submit"
                            x-bind:disabled="!canSaveEdit()"
                            x-bind:class="canSaveEdit()
                                ? 'bg-bu text-white hover:bg-bu-dark border-transparent'
                                : 'bg-gray-200 text-gray-500 cursor-not-allowed border-transparent'"
                            class="rounded-lg border px-3 py-1 text-xs font-semibold leading-4 transition">
                        Save
                    </button>
                    <button type="button"
                            @click="resetEdit()"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1 text-xs font-semibold leading-4 text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if($replies->isNotEmpty())
        <div class="mt-1 rounded-md border-t border-gray-200 pt-1 space-y-1.5">
            @foreach($replies as $reply)
                @php
                    $replyVisibility = (string) ($reply->visibility ?? 'faculty_visible');
                    $replyActionType = (string) ($reply->action_type ?? 'requires_action');
                    $isFacultyReply = (int) ($reply->user_id ?? 0) === (int) ($application->faculty_user_id ?? 0);
                    $isFollowUpConcern = $replyVisibility === 'faculty_visible' && $replyActionType === 'requires_action' && !$isFacultyReply;
                    $replyLabel = $isFacultyReply ? 'Faculty Reply' : 'Reviewer Comment';
                @endphp
                <div class="text-[11px] text-gray-700 @if(!$loop->first) border-t border-gray-200 pt-1.5 @endif">
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-[11px] font-semibold text-gray-800">
                            {{ $reply->author?->name ?? 'Faculty' }} - {{ optional($reply->created_at)->format('M d, Y g:i A') }}
                        </div>
                        @php
                            $replyAuthorRole = strtolower((string) ($reply->author?->role ?? ''));
                            $canManageFollowUp = $currentReviewerRole !== '' && $replyAuthorRole === $currentReviewerRole;
                        @endphp
                    </div>
                    <div class="mt-0.5 text-[10px] font-semibold text-gray-500">{{ $replyLabel }}:</div>
                    <div class="mt-0.5 flex items-start justify-between gap-2">
                        <div class="min-w-0 break-words text-[13px] leading-5 text-gray-800">{{ $reply->body }}</div>
                        @if($isFollowUpConcern && $canManageFollowUp)
                            <form method="POST"
                                  action="{{ route('reclassification.row-comments.destroy', $reply) }}"
                                  data-async-action
                                  data-async-refresh-target="#reviewer-content"
                                  data-loading-text="Removing..."
                                  data-loading-message="Removing follow-up concern..."
                                  data-confirm-modal="1"
                                  data-confirm-title="Remove Follow-up Comment"
                                  data-confirm-confirm-text="Remove"
                                  data-confirm-cancel-text="Cancel"
                                  data-confirm-destructive="1"
                                  data-confirm="Remove this follow-up concern?"
                                  class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-0.5 rounded border border-red-200 bg-red-50 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(
        $canManageThisThread
        &&
        $comment->visibility === 'faculty_visible'
        && ($comment->action_type ?? 'requires_action') === 'requires_action'
        && in_array((string) ($comment->status ?? 'open'), ['addressed', 'resolved'], true)
    )
        <div class="mt-2 space-y-2"
             x-data="{ reopenOpen: false, reopenBody: '' }">
            <div class="flex justify-end gap-2">
                @if(($comment->status ?? 'open') === 'addressed')
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.resolve', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Resolving comment...">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-green-200 bg-green-50 text-[11px] font-semibold text-green-700 hover:bg-green-100">
                            Mark Resolved
                        </button>
                    </form>
                    <button type="button"
                            @click="reopenOpen = !reopenOpen"
                            class="px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-[11px] font-semibold text-amber-700 hover:bg-amber-100">
                        Reopen Comment
                    </button>
                @endif
                @if(($comment->status ?? 'open') === 'resolved')
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.undo-resolve', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Undoing resolved status...">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-blue-200 bg-blue-50 text-[11px] font-semibold text-blue-700 hover:bg-blue-100">
                            Undo Resolved
                        </button>
                    </form>
                @endif
            </div>
            @if(($comment->status ?? 'open') === 'addressed')
                <div x-show="reopenOpen"
                     x-cloak
                     class="rounded-lg border border-amber-200 bg-amber-50/40 p-2.5">
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.reopen', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Reopening comment..."
                          class="space-y-2">
                        @csrf
                        <textarea name="body"
                                  rows="2"
                                  required
                                  x-model="reopenBody"
                                  class="w-full rounded-lg border-gray-300 text-xs"
                                  placeholder="Follow-up concern..."></textarea>
                        <div class="flex justify-end gap-2">
                            <button type="button"
                                    @click="reopenOpen = false; reopenBody = ''"
                                    class="px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-[11px] font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="!String(reopenBody || '').trim()"
                                    :class="!String(reopenBody || '').trim() ? 'opacity-60 cursor-not-allowed' : ''"
                                    class="px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-[11px] font-semibold text-amber-700 hover:bg-amber-100">
                                Reopen as Required Action
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif
</div>
