export default function flowforge({ state }) {
    return {
        state,
        isLoading: {},
        fullyLoaded: {},
        dragOverColumn: null,
        dragOverLane: null,
        dragSource: null,
        stopTrackingPointer: null,

        init() {
            this.$wire.$on('kanban-items-loaded', (event) => {
                const { columnId, isFullyLoaded } = event;

                if (isFullyLoaded) {
                    this.fullyLoaded[columnId] = true;
                }
            });
        },

        configureSortable(el) {
            if (!el.sortable) {
                return;
            }

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            el.sortable.option('scrollSensitivity', 100);
            el.sortable.option('scrollSpeed', 15);
            el.sortable.option('forceAutoScrollFallback', true);
            // Native HTML5 drag lets the browser paint the dragged card, so CSS cannot lift or tilt it.
            el.sortable.option('forceFallback', true);
            el.sortable.option('fallbackOnBody', true);
            el.sortable.option('fallbackClass', 'ff-card-dragging');
            el.sortable.option('ghostClass', 'ff-card-placeholder');
            el.sortable.option('delay', 150);
            el.sortable.option('delayOnTouchOnly', true);
            el.sortable.option('animation', reduceMotion ? 0 : 200);
            el.sortable.option('group', {
                name: el.getAttribute('x-sortable-group'),
                pull: true,
                put: (to, from) => this.canDrop(to.el, from.el),
            });
            el.sortable.option('onStart', (event) => this.startDrag(event));
        },

        startDrag(event) {
            this.dragSource = {
                column: event.from.dataset.columnId,
                lane: event.from.dataset.laneId ?? null,
            };

            const track = (pointerEvent) => this.trackPointer(pointerEvent);

            document.addEventListener('pointermove', track, { passive: true });
            document.addEventListener('touchmove', track, { passive: true });

            this.stopTrackingPointer = () => {
                document.removeEventListener('pointermove', track);
                document.removeEventListener('touchmove', track);
            };
        },

        trackPointer(pointerEvent) {
            const point = pointerEvent.touches?.[0] ?? pointerEvent;
            const target = document.elementFromPoint(point.clientX, point.clientY);
            const cell = target?.closest('[data-column-id]');
            const root = target?.closest('[data-column-root]');

            this.dragOverColumn = cell?.dataset.columnId ?? root?.dataset.columnRoot ?? null;
            this.dragOverLane = cell?.dataset.laneId ?? null;
        },

        endDrag() {
            this.stopTrackingPointer?.();
            this.stopTrackingPointer = null;
            this.dragOverColumn = null;
            this.dragOverLane = null;
            this.dragSource = null;
        },

        canDrop(toEl, fromEl) {
            const toLane = toEl.dataset.laneId ?? null;
            const fromLane = fromEl.dataset.laneId ?? null;

            if (toLane !== fromLane && this.$root.dataset.lanesWritable !== 'true') {
                return false;
            }

            return !this.isColumnBlocked(toEl.dataset.columnId, fromEl.dataset.columnId);
        },

        isColumnBlocked(columnId, sourceColumnId = this.dragSource?.column) {
            if (!columnId || columnId === sourceColumnId) {
                return false;
            }

            const root = this.$root.querySelector(`[data-column-root="${CSS.escape(columnId)}"]`);
            const limit = root?.dataset.columnLimit;

            if (limit === undefined || limit === '') {
                return false;
            }

            return Number(root.dataset.columnTotal) >= Number(limit);
        },

        isCellBlocked(columnId, laneId) {
            if (!this.dragSource) {
                return false;
            }

            if (laneId !== this.dragSource.lane && this.$root.dataset.lanesWritable !== 'true') {
                return true;
            }

            return this.isColumnBlocked(columnId);
        },

        displayCount(columnId, total) {
            const source = this.dragSource?.column;
            const target = this.dragOverColumn;

            if (!source || !target || source === target || this.isColumnBlocked(target)) {
                return total;
            }

            if (columnId === target) {
                return total + 1;
            }

            if (columnId === source) {
                return total - 1;
            }

            return total;
        },

        handleSortableEnd(event) {
            this.endDrag();

            const draggedNode = event.item;
            const parentNode = event.to;
            const dropIndex = event.newDraggableIndex ?? event.newIndex;
            const itemSelector = ':scope > [x-sortable-item], :scope > [data-card-id]';
            const readId = (el) => el.getAttribute('x-sortable-item') || el.getAttribute('data-card-id');

            if (event.from === parentNode && event.oldDraggableIndex === event.newDraggableIndex) {
                return;
            }

            // Filament's x-sortable re-inserts the node after items[dropIndex - 1] (filamentphp/filament#17402)
            // but skips index 0, leaving the DOM stale on a top drop.
            if (dropIndex === 0) {
                const firstItem = parentNode.querySelector(itemSelector);

                if (firstItem && firstItem !== draggedNode) {
                    parentNode.insertBefore(draggedNode, firstItem);
                }
            }

            const cardId = readId(draggedNode);

            if (!cardId) {
                console.error('Flowforge: Could not determine card ID for move operation');
                return;
            }

            const targetColumn = parentNode.getAttribute('data-column-id');

            if (!targetColumn) {
                console.error('Flowforge: Target column ID is missing');
                return;
            }

            const fromLane = event.from.dataset.laneId ?? null;
            const toLane = parentNode.dataset.laneId ?? null;
            const items = parentNode.querySelectorAll(itemSelector);
            const index = Array.prototype.indexOf.call(items, draggedNode);
            const afterCardId = index > 0 ? readId(items[index - 1]) : null;
            const beforeCardId = index >= 0 && index < items.length - 1 ? readId(items[index + 1]) : null;

            this.setCardState(draggedNode, true);

            this.$wire.moveCard(cardId, targetColumn, afterCardId, beforeCardId, fromLane, toLane)
                .then((moved) => {
                    this.setCardState(draggedNode, false);

                    if (moved === false) {
                        this.revertCard(event);
                    }
                })
                .catch(() => {
                    this.setCardState(draggedNode, false);
                    this.revertCard(event);
                });
        },

        revertCard({ item, from, oldDraggableIndex }) {
            item.remove();

            const siblings = from.querySelectorAll(':scope > [x-sortable-item]');
            const anchor = siblings[oldDraggableIndex]
                ?? siblings[siblings.length - 1]?.nextSibling
                ?? from.firstChild;

            from.insertBefore(item, anchor);
        },

        setCardState(cardElement, disabled) {
            cardElement.style.opacity = disabled ? '0.7' : '';
            cardElement.style.pointerEvents = disabled ? 'none' : '';
        },

        isLoadingColumn(columnId) {
            return this.isLoading[columnId] || false;
        },

        isColumnFullyLoaded(columnId) {
            return this.fullyLoaded[columnId] || false;
        },

        handleSmoothScroll(columnId) {
            if (this.isLoadingColumn(columnId) || this.isColumnFullyLoaded(columnId)) {
                return;
            }

            this.isLoading[columnId] = true;

            this.$wire.loadMoreItems(columnId)
                .then(() => setTimeout(() => this.isLoading[columnId] = false, 100))
                .catch(() => this.isLoading[columnId] = false);
        },

        handleColumnScroll(event, columnId) {
            if (this.isColumnFullyLoaded(columnId)) {
                return;
            }

            const { scrollTop, scrollHeight, clientHeight } = event.target;

            if ((scrollTop + clientHeight) / scrollHeight >= 0.8 && !this.isLoadingColumn(columnId)) {
                this.handleSmoothScroll(columnId);
            }
        },
    };
}
