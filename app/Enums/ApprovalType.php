<?php

namespace App\Enums;

enum ApprovalType: string
{
  case DRAFT = 'draft';
  case APPROVED = 'approved';
  case REJECTED = 'rejected';

  /**
   * Get the label for the approval type.
   */
  public function label(): string
  {
    return match ($this) {
      self::DRAFT => 'Waiting Approval',
      self::APPROVED => 'Approved',
      self::REJECTED => 'Rejected',
    };
  }

  /**
   * Get the description for the approval type.
   */
  public function description(): string
  {
    return match ($this) {
      self::DRAFT => 'Waiting for approval',
      self::APPROVED => 'Approved by manager or supervisor',
      self::REJECTED => 'Rejected by manager or supervisor',
    };
  }

  /**
   * Get the color for the approval type.
   * 
   * @return string tailwind color class
   */
  public function color(): string
  {
    return match ($this) {
      self::DRAFT => 'bg-base-500',
      self::APPROVED => 'bg-green-500',
      self::REJECTED => 'bg-red-500',
    };
  }
}
