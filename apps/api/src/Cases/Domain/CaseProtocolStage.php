<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseProtocolStage: string
{
    case Identification = 'identification';
    case ImmediateActions = 'immediate_actions';
    case UrgentProtection = 'urgent_protection';
    case FamilyCommunication = 'family_communication';
    case ProfessionalCoordination = 'professional_coordination';
    case InformationCollection = 'information_collection';
    case EducationalMeasures = 'educational_measures';
    case InspectionCommunication = 'inspection_communication';
    case Assessment = 'assessment';
    case ActionPlan = 'action_plan';
    case FamilyMeasures = 'family_measures';
    case InspectionFollowUp = 'inspection_follow_up';
}
