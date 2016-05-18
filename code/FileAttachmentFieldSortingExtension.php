<?php

/**
 * Class FileAttachmentFieldSortingExtension
 *
 * @property FileAttachmentField $owner
 */
class FileAttachmentFieldSortingExtension extends DataExtension
{

    private static $allowed_actions = [
        'sort'
    ];

    public function setSortableColumn($name)
    {
        $this->owner->setSetting('sort-column', $name);

        return $this->owner;
    }

    public function getSortableColumn($default = 'SortOrder')
    {
        return $this->owner->getSetting('sort-column') ?: $default;
    }

    /**
     * Set whether the files are sortable
     *
     * @param $val
     *
     * @return \FileAttachmentField
     */
    public function setSortable($val)
    {
        $this->owner->setSetting('sortable', $val);
        return $this->owner;
    }

    /**
     * Enable sorting
     */
    public function sortable()
    {
        return $this->setSortable(true);
    }

    public function onBeforeRender(FileAttachmentField $field)
    {
        if ($field->getSetting('sortable') && $this->owner->isCMS()) {
            $field->setSetting('sortable-action', $field->Link('sort'));
            $field->setSetting('sort-column', $this->getSortableColumn());
            $field->addExtraClass('is-sortable');

            Requirements::javascript(DROPZONE_SORTABLE_DIR . '/javascript/dropzone-sortable.js');
            Requirements::css(DROPZONE_SORTABLE_DIR . '/css/dropzone-sortable.css');
        }
    }

    public function updateAttachedFiles(&$attachedFiles)
    {
        if ($this->owner->getSetting('sortable')) {
            $attachedFiles = $attachedFiles->sort($this->getSortableColumn());
        }
    }

    /**
     * Action to handle sorting of a single file
     *
     * @param SS_HTTPRequest $request
     */
    public function sort(SS_HTTPRequest $request)
    {
        $controller = Controller::curr();

        //die(json_encode($request->allParams()));

        // Check if a new position is given
        $newPosition = $request->getVar('newPosition');
        $oldPosition = $request->getVar('oldPosition');
        $fileID      = $request->shift();
        if ($newPosition === "") {
            $controller->httpError(403);
        }
        // Check form field state
        if ($this->owner->isDisabled() || $this->owner->isReadonly()) {
            $controller->httpError(403);
        }
        // Check item permissions
        $itemMoved = DataObject::get_by_id('File', $fileID);
        if (!$itemMoved) {
            $controller->httpError(404);
        }
        if (!$itemMoved->canEdit()) {
            $controller->httpError(403);
        }
        // Only allow actions on files in the managed relation (if one exists)
        $sortColumn   = $this->getSortableColumn();
        $relationName = $this->owner->getName();
        $record       = $this->owner->getRecord();
        if ($record && $record->hasMethod($relationName)) {
            /** @var HasManyList|ManyManyList $list */
            $list           = $record->$relationName();
            $list           = $list->sort($sortColumn, 'ASC');
            $listForeignKey = $list->getForeignKey();
            $is_many_many   = $record->manyMany($relationName) !== null;
            $i              = 0;
            $newPosition    = intval($newPosition);
            $oldPosition    = intval($oldPosition);
            $arrayList      = $list->toArray();
            $itemIsInList   = false;
            foreach ($arrayList as $item) {
                /** @var File $item */
                if ($item->ID == $itemMoved->ID) {
                    $sort = $newPosition;
                    // flag that we found our item in the list
                    $itemIsInList = true;
                } else {
                    if ($i >= $newPosition && $i < $oldPosition) {
                        $sort = $i + 1;
                    } else {
                        if ($i <= $newPosition && $i > $oldPosition) {
                            $sort = max(0, $i - 1);
                        } else {
                            $sort = $i;
                        }
                    }
                }
                if ($is_many_many) {
                    $list->remove($item);
                    $list->add($item, array($sortColumn => $sort + 1));
                } else {
                    if (!$item->exists()) {
                        $item->write();
                    }
                    $item->$sortColumn = $sort + 1;
                    $item->write();
                }
                $i++;
            }
            // if the item wasn't in our list, add it now with the new sort position
            if (!$itemIsInList) {
                if ($is_many_many) {
                    $list->add($itemMoved, array($sortColumn => $newPosition + 1));
                } else {
                    $itemMoved->$listForeignKey = $record->ID;
                    $itemMoved->$sortColumn     = intval($newPosition + 1);
                    $itemMoved->write();
                }
            }
            Requirements::clear();

            return "1";
        }
        $controller->httpError(403);
    }
}
