<?php

interface IGrouperTranslator {

    function getGroupField();

    function getSelectField();

    function getOrderField();

    function translateForCharges(Criteria $criteria);

    function translateForErrands(Criteria $criteria);

    function translateForWorks(Criteria $criteria);

}