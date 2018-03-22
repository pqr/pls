import * as React from 'react';
import {observer, inject} from "mobx-react";
import UserModuleStore from "../../UserModuleStore";

@inject('store') @observer
export default class UsersFilters extends React.Component<{store?:UserModuleStore}, {}> {

    render() {

        const filters = this.props.store!.usersFilters;
        const groupsOrderedArray = this.props.store!.groupsOrderedArray;

        return (
            <div className="filters">

                <input type="text"
                       className="input-medium"
                       value={filters.name}
                       placeholder="Name / Login / Email"
                       onChange={(e:any) => filters.onChange('name', e.target.value)}
                       ref={(input) => {if (input != null) {input.focus();}}}
                />

                <select value={filters.groupId}
                        style={{opacity: filters.groupId === '' ? .5 : 1}}
                        onChange={(e:any) => filters.onChange('groupId', e.target.value)}>
                    <option value="">Group: Any</option>
                    {groupsOrderedArray.map(({ID, Name}) => <option value={ID} key={ID}>{Name}</option>)}
                </select>

                <select value={filters.active}
                        style={{opacity: filters.active === '' ? .5 : 1}}
                        onChange={(e:any) => filters.onChange('active', e.target.value)}>
                    <option value="" style={{opacity: .5}}>Active: Any</option>
                    <option className="good" value="1">Active: Yes</option>
                    <option className="bad" value="0">Active: No</option>
                </select>

            </div>
        )

    }

}