import * as React from 'react';
type Props = {
    objectErrors: string[];
    onBack?: (e: React.MouseEvent) => void;
};
export default function ItemErrors({ objectErrors, onBack }: Props): JSX.Element;
export {};
